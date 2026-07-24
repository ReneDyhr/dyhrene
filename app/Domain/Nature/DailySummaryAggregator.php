<?php

declare(strict_types=1);

namespace App\Domain\Nature;

use App\Models\DailySpeciesSummary;
use App\Models\Observation;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use Carbon\CarbonImmutable;

/**
 * Maintains the daily_species_summaries rollup table.
 *
 * Re-derives from observation_windows on every change.
 * Designed to be called from the ObservationObserver.
 */
final class DailySummaryAggregator
{
    /**
     * Recompute the daily summary for a given site, species, and local date.
     */
    public function recomputeDay(Site $site, Species $species, CarbonImmutable $date): void
    {
        $dateStr = $date->format('Y-m-d');
        $dayStart = $date->startOfDay();
        $dayEnd = $date->endOfDay();

        $windows = ObservationWindow::query()
            ->where('site_id', $site->id)
            ->where('species_id', $species->id)
            ->whereBetween('window_start', [
                $dayStart->toDateTimeString(),
                $dayEnd->toDateTimeString(),
            ])
            ->get();

        if ($windows->isEmpty()) {
            DailySpeciesSummary::query()
                ->where('site_id', $site->id)
                ->where('date', $dateStr)
                ->where('species_id', $species->id)
                ->delete();

            return;
        }

        $windowsPresent = $windows->count();

        /** @var int $totalRecords */
        $totalRecords = $windows->sum('records');

        /** @var list<string> $sources */
        $sources = $windows->pluck('source')
            ->unique()
            ->values()
            ->toArray();

        // First and last observation times within this day
        $firstObs = Observation::query()
            ->where('site_id', $site->id)
            ->where('species_id', $species->id)
            ->where('local_date', $dateStr)
            ->orderBy('local_time', 'asc')
            ->first();

        $lastObs = Observation::query()
            ->where('site_id', $site->id)
            ->where('species_id', $species->id)
            ->where('local_date', $dateStr)
            ->orderBy('local_time', 'desc')
            ->first();

        $firstSeenAt = null;

        if ($firstObs !== null) {
            $lt = $firstObs->local_time ?? '00:00:00';
            $firstSeenAt = $date->setTimeFromTimeString($lt)->toDateTimeString();
        }

        $lastSeenAt = null;

        if ($lastObs !== null) {
            $lt = $lastObs->local_time ?? '00:00:00';
            $lastSeenAt = $date->setTimeFromTimeString($lt)->toDateTimeString();
        }

        DailySpeciesSummary::query()->updateOrCreate(
            [
                'site_id' => $site->id,
                'date' => $dateStr,
                'species_id' => $species->id,
            ],
            [
                'windows_present' => $windowsPresent,
                'records' => $totalRecords,
                'sources' => \json_encode($sources),
                'first_seen_at' => $firstSeenAt,
                'last_seen_at' => $lastSeenAt,
            ],
        );
    }
}
