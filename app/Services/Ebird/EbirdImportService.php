<?php

declare(strict_types=1);

namespace App\Services\Ebird;

use App\Models\Observation;
use App\Models\Species;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

final class EbirdImportService
{
    private const string LOGIN_URL = 'https://secure.birds.cornell.edu/cassso/login';

    private Client $client;

    private CookieJar $cookieJar;

    public function __construct()
    {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies' => $this->cookieJar,
            'timeout' => 60,
            'allow_redirects' => ['max' => 10],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Dyhrene/1.0)',
            ],
        ]);
    }

    /**
     * Import all eBird observations for a user.
     *
     * @return array{species_created: int, observations_created: int, observations_enriched: int, errors: list<string>}
     */
    public function import(User $user, string $ebirdUser, string $ebirdPass): array
    {
        $stats = [
            'species_created' => 0,
            'observations_created' => 0,
            'observations_enriched' => 0,
            'errors' => [],
        ];

        try {
            $this->login($ebirdUser, $ebirdPass);
        } catch (\Throwable $e) {
            $stats['errors'][] = 'Login failed: ' . $e->getMessage();
            Log::error('eBird login failed', ['error' => $e->getMessage()]);

            return $stats;
        }

        $speciesCodes = [];

        try {
            $speciesCodes = $this->fetchSpeciesCodes();
        } catch (\Throwable $e) {
            $stats['errors'][] = 'Failed to fetch species codes: ' . $e->getMessage();
            Log::error('eBird species codes fetch failed', ['error' => $e->getMessage()]);

            return $stats;
        }

        if ($speciesCodes === []) {
            $stats['errors'][] = 'No species codes found on lifelist page.';

            return $stats;
        }

        foreach ($speciesCodes as $code) {
            try {
                $rows = $this->fetchSpeciesCsv($code);
            } catch (\Throwable $e) {
                $stats['errors'][] = "Failed to fetch CSV for species {$code}: " . $e->getMessage();
                Log::warning('eBird species CSV fetch failed', ['code' => $code, 'error' => $e->getMessage()]);

                continue;
            }

            /** @var array<string, string> $row */
            foreach ($rows as $row) {
                $commonName = $row['Common Name'] ?? ($row['Species'] ?? '');
                $scientificName = $row['Scientific Name'] ?? null;
                $taxonomicOrder = isset($row['Taxon Order']) ? (int) $row['Taxon Order'] : null;

                if ($commonName === '') {
                    continue;
                }

                /** @var Species $species */
                $species = Species::query()->firstOrCreate(
                    [
                        'common_name' => $commonName,
                        'user_id' => $user->id,
                    ],
                    [
                        'scientific_name' => $scientificName,
                        'ebird_code' => $code,
                        'taxonomic_order' => $taxonomicOrder,
                    ],
                );

                if ($species->wasRecentlyCreated) {
                    $stats['species_created']++;
                } elseif ($species->ebird_code === null) {
                    $species->update(['ebird_code' => $code]);
                }

                $submissionId = $row['SubID'] ?? null;

                if ($submissionId === null || $submissionId === '') {
                    continue;
                }

                $observedAt = $this->parseDate($row['Date'] ?? null);

                /** @var Observation $observation */
                $observation = Observation::query()->firstOrCreate(
                    ['ebird_submission_id' => $submissionId],
                    [
                        'species_id' => $species->id,
                        'user_id' => $user->id,
                        'observed_at' => $observedAt,
                        'count' => $row['Count'] ?? null,
                        'location' => $row['Location'] ?? null,
                        'state_province' => $row['S/P'] ?? null,
                        'source' => 'ebird_import',
                    ],
                );

                if ($observation->wasRecentlyCreated) {
                    $stats['observations_created']++;
                }
            }
        }

        // Enrich observations with Merlin checklist data
        try {
            $stats['observations_enriched'] = $this->enrichObservations($user);
        } catch (\Throwable $e) {
            $stats['errors'][] = 'Merlin enrichment failed: ' . $e->getMessage();
            Log::error('Merlin enrichment failed', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /**
     * Login to eBird via Cornell CAS.
     */
    private function login(string $username, string $password): void
    {
        $service = 'https://ebird.org/login/cas?portal=ebird';

        // Step 1: GET the login page to get cookies and CSRF execution token
        $loginPage = $this->client->get(self::LOGIN_URL, [
            'query' => ['service' => $service],
        ]);
        $html = (string) $loginPage->getBody();

        // Extract the execution token from the hidden input
        \preg_match('/name="execution"\s+value="([^"]+)"/', $html, $m);
        $execution = $m[1] ?? null;

        if ($execution === null) {
            throw new \RuntimeException('Could not extract execution token from CAS login form.');
        }

        // Step 2: POST the login form
        $this->client->post(self::LOGIN_URL, [
            'form_params' => [
                'username'   => $username,
                'password'   => $password,
                'execution'  => $execution,
                '_eventId'   => 'submit',
                'service'    => $service,
                'rememberMe' => 'on',
            ],
            'headers' => [
                'Referer'       => self::LOGIN_URL . '?service=' . urlencode($service),
                'Origin'        => 'https://secure.birds.cornell.edu',
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
        ]);
    }

    /**
     * Fetch all species codes from the world lifelist page.
     *
     * @return list<string>
     */
    private function fetchSpeciesCodes(): array
    {
        $response = $this->client->get('https://ebird.org/lifelist/world');
        $html = (string) $response->getBody();

        \preg_match_all('/spp=([A-Za-z0-9]+)/', $html, $matches);

        return \array_values(\array_unique($matches[1]));
    }

    /**
     * Download and parse the CSV for a single species.
     *
     * @return list<array<string, string>>
     */
    private function fetchSpeciesCsv(string $code): array
    {
        $response = $this->client->get("https://ebird.org/lifelist?r=world&spp={$code}&time=life&fmt=csv");
        $csv = (string) $response->getBody();

        return $this->parseCsv($csv);
    }

    /**
     * Enrich observations with data from Merlin checklist downloads.
     *
     * @return int Number of observations enriched
     */
    private function enrichObservations(User $user): int
    {
        // Get all observations for the user that need enrichment
        $observations = Observation::query()
            ->where('user_id', $user->id)
            ->where('source', 'ebird_import')
            ->whereNull('observation_type')
            ->get();

        if ($observations->isEmpty()) {
            return 0;
        }

        $enriched = 0;
        $processedSubIds = [];

        foreach ($observations as $observation) {
            $subId = $observation->ebird_submission_id;

            if ($subId === null || $subId === '') {
                continue;
            }

            if (\in_array($subId, $processedSubIds, true)) {
                continue;
            }

            $checklistData = $this->fetchChecklist($subId);

            if ($checklistData === null) {
                continue;
            }

            // Update all observations with this submission ID (only non-null fields)
            $updateData = \array_filter([
                'observed_time' => $checklistData['observed_time'] ?? null,
                'observation_type' => $checklistData['observation_type'] ?? null,
                'duration_min' => $checklistData['duration_min'] ?? null,
                'distance_km' => $checklistData['distance_km'] ?? null,
                'area_ha' => $checklistData['area_ha'] ?? null,
                'observer_count' => $checklistData['observer_count'] ?? null,
                'complete_checklist' => $checklistData['complete_checklist'] ?? false,
            ], fn ($v) => $v !== null);

            if ($updateData !== []) {
                Observation::query()
                    ->where('ebird_submission_id', $subId)
                    ->update($updateData);
            }

            $enriched += Observation::query()
                ->where('ebird_submission_id', $subId)
                ->count();

            $processedSubIds[] = $subId;
        }

        return $enriched;
    }

    /**
     * Fetch checklist metadata from Merlin for a given submission ID.
     *
     * @return null|array<string, mixed>
     */
    private function fetchChecklist(string $subId): ?array
    {
        try {
            $response = $this->client->get("https://ebird.org/merlin/checklist/download?subID={$subId}");
            $content = (string) $response->getBody();

            return $this->parseChecklist($content);
        } catch (GuzzleException $e) {
            Log::warning('Merlin checklist fetch failed', ['subId' => $subId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Parse Merlin checklist download content into structured data.
     *
     * @return array<string, mixed>
     */
    private function parseChecklist(string $content): array
    {
        // Merlin returns CSV: Species,Count,Location,Observation Type,Observation Date,Start Time,...
        $rows = $this->parseCsv($content);

        if ($rows === []) {
            return [
                'observed_time' => null,
                'observation_type' => null,
                'duration_min' => null,
                'distance_km' => null,
                'area_ha' => null,
                'observer_count' => null,
                'complete_checklist' => false,
            ];
        }

        $row = $rows[0];

        $time = $row['Start Time'] ?? null;
        $duration = isset($row['Duration']) && $row['Duration'] !== '' ? (int) $row['Duration'] : null;
        $distance = isset($row['Distance']) && $row['Distance'] !== '' ? (float) $row['Distance'] : null;
        $area = isset($row['Area']) && $row['Area'] !== '' ? (float) $row['Area'] : null;
        $partySize = isset($row['Party Size']) && $row['Party Size'] !== '' ? (int) $row['Party Size'] : null;
        $complete = ($row['Complete Checklist'] ?? 'false') === 'true';

        return [
            'observed_time' => $time !== null && $time !== '' ? $this->parseTimeString((string) $time) : null,
            'observation_type' => $row['Observation Type'] ?? null,
            'duration_min' => $duration,
            'distance_km' => $distance,
            'area_ha' => $area,
            'observer_count' => $partySize,
            'complete_checklist' => $complete,
        ];
    }

    /**
     * Parse a CSV string into an array of associative arrays.
     *
     * @return list<array<string, string>>
     */
    private function parseCsv(string $csv): array
    {
        $lines = \explode("\n", \trim($csv));

        if (\count($lines) < 2) {
            return [];
        }

        $headerLine = \array_shift($lines);

        /** @var list<string> $header */
        $header = \str_getcsv($headerLine);

        if ($header === [] || (\count($header) === 1 && $header[0] === '')) {
            return [];
        }

        $rows = [];

        foreach ($lines as $line) {
            $line = \trim($line);

            if ($line === '') {
                continue;
            }

            $values = \str_getcsv($line);

            if (\count($values) !== \count($header)) {
                continue;
            }

            /** @var array<string, string> $row */
            $row = \array_combine($header, $values);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Parse a date string into a date-only format.
     */
    private function parseDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            $parsed = new \DateTimeImmutable($date);

            return $parsed->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse a human-readable time string into H:i:s format.
     */
    private function parseTimeString(string $time): ?string
    {
        $time = \trim($time);

        try {
            $parsed = new \DateTimeImmutable($time);

            return $parsed->format('H:i:s');
        } catch (\Throwable) {
            // Try basic HH:MM pattern
            if (\preg_match('/(\d{1,2}):(\d{2})/', $time, $m) === 1) {
                return \sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
            }

            return null;
        }
    }
}
