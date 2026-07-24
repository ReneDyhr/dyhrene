<?php

declare(strict_types=1);

namespace App\Domain\Nature;

use App\Enums\ObservationSourceEnum;
use App\Models\Observation;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use Carbon\CarbonImmutable;

/**
 * Maintains the observation_windows rollup table.
 *
 * Upserts a single window row on every observation create/update/delete.
 * Designed to be called from the ObservationObserver.
 */
final class ObservationWindowAggregator
{
    /**
     * Given a specific 10-minute window, recount the underlying observations
     * from the observations table and upsert the rollup row.
     */
    public function recomputeWindow(Site $site, Species $species, CarbonImmutable $windowStart, ObservationSourceEnum $source): void
    {
        $windowEnd = $windowStart->addMinutes(10);

        // windowStart is already in the site's local timezone
        $localDate = $windowStart->format('Y-m-d');
        $localTimeStart = $windowStart->format('H:i:s');
        $localTimeEnd = $windowEnd->format('H:i:s');

        $count = Observation::query()
            ->where('observations.site_id', $site->id)
            ->where('observations.species_id', $species->id)
            ->where('observations.source', $source->value)
            ->where('observations.local_date', $localDate)
            ->where('observations.local_time', '>=', $localTimeStart)
            ->where('observations.local_time', '<', $localTimeEnd)
            ->count();

        if ($count === 0) {
            // No observations — remove the window row if it exists
            ObservationWindow::query()
                ->where('site_id', $site->id)
                ->where('species_id', $species->id)
                ->where('window_start', $windowStart->toDateTimeString())
                ->where('source', $source->value)
                ->delete();

            return;
        }

        // Compute max confidence (BirdNET only)
        $maxConf = null;

        if ($source === ObservationSourceEnum::Birdnet) {
            /** @var null|string $maxConfRaw */
            $maxConfRaw = Observation::query()
                ->where('observations.site_id', $site->id)
                ->where('observations.species_id', $species->id)
                ->where('observations.source', $source->value)
                ->where('observations.local_date', $windowStart->format('Y-m-d'))
                ->where('observations.local_time', '>=', $windowStart->format('H:i:s'))
                ->where('observations.local_time', '<', $windowEnd->format('H:i:s'))
                ->join('birdnet_detections', 'observations.id', '=', 'birdnet_detections.observation_id')
                ->max('birdnet_detections.confidence');

            if (\is_string($maxConfRaw)) {
                $maxConf = (float) $maxConfRaw;
            }
        }

        ObservationWindow::query()->updateOrCreate(
            [
                'site_id' => $site->id,
                'species_id' => $species->id,
                'window_start' => $windowStart->toDateTimeString(),
                'source' => $source->value,
            ],
            [
                'records' => $count,
                'max_confidence' => $maxConf,
            ],
        );
    }

    /**
     * Determine the 10-minute window start for a given observation's local datetime.
     */
    public static function windowStartFor(string $localDate, ?string $localTime): CarbonImmutable
    {
        $tz = new \DateTimeZone('Europe/Copenhagen');
        $timePart = $localTime !== null && $localTime !== '' ? $localTime : '00:00:00';
        $dt = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$localDate} {$timePart}", $tz)
            ?: CarbonImmutable::now($tz);

        return $dt->floorMinutes(10);
    }
}
