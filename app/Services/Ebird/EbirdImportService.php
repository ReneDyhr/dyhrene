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
            'allow_redirects' => true,
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
                $taxonomicOrder = isset($row['Taxonomic Order']) ? (int) $row['Taxonomic Order'] : null;

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

                $submissionId = $row['Submission ID'] ?? null;

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
                        'state_province' => $row['State/Province'] ?? ($row['State'] ?? null),
                        'source' => 'ebird',
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
        $this->client->post(self::LOGIN_URL, [
            'form_params' => [
                'username' => $username,
                'password' => $password,
                'execution' => 'e1s1',
                '_eventId' => 'submit',
                'lt' => '',
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
            ->where('source', 'ebird')
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

            // Update all observations with this submission ID
            Observation::query()
                ->where('ebird_submission_id', $subId)
                ->update([
                    'observed_time' => $checklistData['observed_time'] ?? null,
                    'observation_type' => $checklistData['observation_type'] ?? null,
                    'duration_min' => $checklistData['duration_min'] ?? null,
                    'distance_km' => $checklistData['distance_km'] ?? null,
                    'area_ha' => $checklistData['area_ha'] ?? null,
                    'observer_count' => $checklistData['observer_count'] ?? null,
                    'complete_checklist' => $checklistData['complete_checklist'] ?? false,
                ]);

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
        $data = [
            'observed_time' => null,
            'observation_type' => null,
            'duration_min' => null,
            'distance_km' => null,
            'area_ha' => null,
            'observer_count' => null,
            'complete_checklist' => false,
        ];

        // Try to parse as CSV
        $lines = \explode("\n", \trim($content));

        if (\count($lines) < 2) {
            return $data;
        }

        // Look for key-value patterns in the content
        if (\preg_match('/Time[:\s]+(\d{1,2}:\d{2}(?::\d{2})?(?:\s*[APap][Mm])?)/', $content, $m) === 1) {
            $data['observed_time'] = $this->parseTimeString($m[1]);
        }

        if (\preg_match('/Protocol[:\s]+([^\n\r]+)/', $content, $m) === 1) {
            $data['observation_type'] = \trim($m[1]);
        }

        if (\preg_match('/Duration[:\s]+(\d+(?:\.\d+)?)/', $content, $m) === 1) {
            $data['duration_min'] = (int) $m[1];
        }

        if (\preg_match('/Distance[:\s]+(\d+(?:\.\d+)?)/', $content, $m) === 1) {
            $data['distance_km'] = (float) $m[1];
        }

        if (\preg_match('/Area[:\s]+(\d+(?:\.\d+)?)/', $content, $m) === 1) {
            $data['area_ha'] = (float) $m[1];
        }

        if (\preg_match('/(?:Observers|Number of Observers|Party Size)[:\s]+(\d+)/', $content, $m) === 1) {
            $data['observer_count'] = (int) $m[1];
        }

        if (\preg_match('/All Obs(?:ervations)? Reported[:\s]*(Yes|1|true)/i', $content) === 1) {
            $data['complete_checklist'] = true;
        }

        return $data;
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
