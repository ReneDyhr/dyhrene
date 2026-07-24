<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Nature\DailySummaryAggregator;
use App\Domain\Nature\ObservationLocalizer;
use App\Domain\Nature\ObservationSolarizer;
use App\Domain\Nature\ObservationWindowAggregator;
use App\Models\Observation;
use App\Models\ObservationWindow;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class BackfillNatureDataCommand extends Command
{
    private const int CHUNK_SIZE = 500;

    protected $signature = 'nature:backfill
                            {--site= : Limit to a specific site ID}
                            {--from= : Starting date (YYYY-MM-DD)}';

    protected $description = 'Backfill site_id, local columns, solar columns, and rebuild rollup tables. Idempotent and re-runnable.';

    public function handle(): int
    {
        $siteId = $this->option('site');
        $from = $this->option('from');

        $site = null;

        if ($siteId !== null && $siteId !== '') {
            /** @var null|Site $site */
            $site = Site::query()->find((int) $siteId);

            if ($site === null) {
                $this->error("Site {$siteId} not found.");

                return self::FAILURE;
            }
        }

        $locale = new ObservationLocalizer();
        $solarizer = new ObservationSolarizer();

        // Step 1: Backfill site_id and location_raw
        $this->info('Step 1: Backfilling site_id and location_raw...');
        $observationQuery = Observation::query()
            ->whereNull('site_id');

        if ($site !== null) {
            $observationQuery->where('user_id', $site->user_id);
        }

        if ($from !== null && $from !== '') {
            $observationQuery->where('observed_at', '>=', $from);
        }

        $count = 0;
        $observationQuery->chunk(self::CHUNK_SIZE, function (\Illuminate\Database\Eloquent\Collection $observations) use ($site, &$count): void {
            foreach ($observations as $obs) {
                $resolvedSite = $this->resolveSiteForObservation($obs, $site);

                if ($resolvedSite === null) {
                    continue;
                }

                // Copy location to location_raw if not set
                $update = [
                    'site_id' => $resolvedSite->id,
                    'location_raw' => $obs->location_raw ?? $obs->location,
                ];

                $obs->updateQuietly($update);
                $count++;
            }
        });
        $this->info("  Backfilled {$count} observations.");

        // Step 2: Recompute local/solar columns
        $this->info('Step 2: Recomputing local and solar columns...');
        $obsQuery = Observation::query()
            ->whereNotNull('site_id');

        if ($site !== null) {
            $obsQuery->where('site_id', $site->id);
        }

        if ($from !== null && $from !== '') {
            $obsQuery->where('observed_at', '>=', $from);
        }

        $localCount = 0;
        $obsQuery->chunk(self::CHUNK_SIZE, function (\Illuminate\Database\Eloquent\Collection $observations) use ($locale, $solarizer, &$localCount): void {
            foreach ($observations as $obs) {
                /** @var null|\App\Models\Site $obsSite */
                $obsSite = $obs->site()->first();

                if ($obsSite === null) {
                    continue;
                }

                $observedAt = \Carbon\Carbon::parse($obs->observed_at)->format('Y-m-d');

                $localized = $locale->localize($observedAt, $obs->observed_time, $obsSite);
                $localDt = $locale->toLocalCarbon($observedAt, $obs->observed_time, $obsSite);
                $solar = $solarizer->solarize($localDt, $obsSite);

                $obs->updateQuietly([
                    'local_date' => $localized['local_date'],
                    'local_time' => $localized['local_time'],
                    'day_of_year' => $localized['day_of_year'],
                    'minutes_from_sunrise' => $solar['minutes_from_sunrise'],
                    'minutes_from_sunset' => $solar['minutes_from_sunset'],
                ]);
                $localCount++;
            }
        });
        $this->info("  Recalculated {$localCount} observations.");

        // Step 3: Rebuild observation_windows
        $this->info('Step 3: Rebuilding observation_windows...');
        ObservationWindow::query()->truncate();
        $this->rebuildWindows($site, $from);
        $windowCount = ObservationWindow::query()->count();
        $this->info("  Rebuilt {$windowCount} observation windows.");

        // Step 4: Rebuild daily_species_summaries
        $this->info('Step 4: Rebuilding daily_species_summaries...');
        $dailyAggregator = new DailySummaryAggregator();
        $this->rebuildDailySummaries($dailyAggregator, $site, $from);
        $dailyCount = \App\Models\DailySpeciesSummary::query()->count();
        $this->info("  Rebuilt {$dailyCount} daily species summaries.");

        $this->info('Backfill complete.');

        return self::SUCCESS;
    }

    private function rebuildWindows(?Site $limitSite, ?string $from): void
    {
        $query = Observation::query()
            ->whereNotNull('site_id')
            ->whereNotNull('local_date');

        if ($limitSite !== null) {
            $query->where('site_id', $limitSite->id);
        }

        if ($from !== null && $from !== '') {
            $query->where('observed_at', '>=', $from);
        }

        $aggregator = new ObservationWindowAggregator();

        $query->chunk(self::CHUNK_SIZE, function (\Illuminate\Database\Eloquent\Collection $observations) use ($aggregator): void {
            foreach ($observations as $obs) {
                $site = $obs->site()->first();
                $species = $obs->species()->first();

                if ($site === null || $species === null) {
                    continue;
                }

                $localDate = \Carbon\Carbon::parse($obs->local_date)->format('Y-m-d');
                $sourceEnum = $obs->source;

                $windowStart = ObservationWindowAggregator::windowStartFor($localDate, $obs->local_time);
                // @phpstan-ignore-next-line argument.type
                $aggregator->recomputeWindow($site, $species, $windowStart, $sourceEnum);
            }
        });
    }

    private function rebuildDailySummaries(DailySummaryAggregator $aggregator, ?Site $limitSite, ?string $from): void
    {
        \App\Models\DailySpeciesSummary::query()->truncate();

        $query = ObservationWindow::query()
            ->selectRaw('DISTINCT site_id, species_id, DATE(window_start) as local_date');

        if ($limitSite !== null) {
            $query->where('site_id', $limitSite->id);
        }

        $pairs = $query->toBase()->get();

        foreach ($pairs as $pair) {
            /** @var null|Site $site */
            $site = Site::query()->find($pair->site_id);

            /** @var null|\App\Models\Species $species */
            $species = \App\Models\Species::query()->find($pair->species_id);

            if ($site === null || $species === null) {
                continue;
            }

            $localDate = (string) $pair->local_date;
            $date = CarbonImmutable::createFromFormat('Y-m-d', $localDate, new \DateTimeZone($site->timezone))
                ?: CarbonImmutable::now();

            $aggregator->recomputeDay($site, $species, $date);
        }
    }

    private function resolveSiteForObservation(Observation $obs, ?Site $defaultSite): ?Site
    {
        // Map known location strings to the acoustic station site
        $locationStr = $obs->location;

        if ($locationStr === null || $locationStr === '') {
            return $defaultSite;
        }

        // "55.6761, 12.5683" → Copenhagen placeholder, map to acoustic station
        // "Jels Skovvej 17" → field site string, map to acoustic station
        // Both map to the user's first site (acoustic station)
        if ($defaultSite !== null) {
            return $defaultSite;
        }

        /** @var null|Site $site */
        $site = Site::query()
            ->where('user_id', $obs->user_id)
            ->first();

        return $site;
    }
}
