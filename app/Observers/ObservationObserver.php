<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Nature\DailySummaryAggregator;
use App\Domain\Nature\ObservationLocalizer;
use App\Domain\Nature\ObservationSolarizer;
use App\Domain\Nature\ObservationWindowAggregator;
use App\Models\Observation;
use App\Models\Site;

class ObservationObserver
{
    private ObservationLocalizer $localizer;

    private ObservationSolarizer $solarizer;

    private ObservationWindowAggregator $windowAggregator;

    private DailySummaryAggregator $dailyAggregator;

    public function __construct()
    {
        $this->localizer = new ObservationLocalizer();
        $this->solarizer = new ObservationSolarizer();
        $this->windowAggregator = new ObservationWindowAggregator();
        $this->dailyAggregator = new DailySummaryAggregator();
    }

    /**
     * Before save: compute local_date, local_time, day_of_year, and solar columns.
     */
    public function saving(Observation $observation): void
    {
        try {
            $this->doSaving($observation);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ObservationObserver::saving failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * After save: recompute rollup windows and daily summary.
     */
    public function saved(Observation $observation): void
    {
        $this->updateRollups($observation);
    }

    /**
     * After delete: recompute rollups for the affected site/species.
     */
    public function deleted(Observation $observation): void
    {
        $this->updateRollups($observation);
    }

    private function doSaving(Observation $observation): void
    {
        $site = $this->resolveSite($observation);

        if ($site === null) {
            return;
        }

        // Set site_id from resolved site if not already set
        if ($observation->site_id === null) {
            $observation->site_id = $site->id; // @phpstan-ignore assign.propertyType
        }

        // Compute local date/time from UTC observations
        $observedAt = \Carbon\Carbon::parse($observation->observed_at);
        $localized = $this->localizer->localize(
            $observedAt->format('Y-m-d'),
            $observation->observed_time,
            $site,
        );

        // Only set if not already set (respects manually set values)
        if ($observation->local_date === null || $observation->local_date === '') {
            $observation->local_date = $localized['local_date'];
        }

        if ($observation->local_time === null) {
            $observation->local_time = $localized['local_time'];
        }

        if ($observation->day_of_year === null) {
            $observation->day_of_year = $localized['day_of_year'];
        }

        // Compute solar position using local datetime
        $observedAt = \Carbon\Carbon::parse($observation->observed_at);
        $localDt = $this->localizer->toLocalCarbon(
            $observedAt->format('Y-m-d'),
            $observation->observed_time,
            $site,
        );

        $solar = $this->solarizer->solarize($localDt, $site);

        if ($observation->minutes_from_sunrise === null) {
            $observation->minutes_from_sunrise = $solar['minutes_from_sunrise'];
        }

        if ($observation->minutes_from_sunset === null) {
            $observation->minutes_from_sunset = $solar['minutes_from_sunset'];
        }
    }

    private function updateRollups(Observation $observation): void
    {
        $site = $this->resolveSite($observation);

        if ($site === null) {
            return;
        }

        $species = $observation->species()->first();

        if ($species === null) {
            return;
        }

        // Ensure local columns exist
        $localDate = $observation->local_date !== null
            ? \Carbon\Carbon::parse($observation->local_date)
            : null;

        if ($localDate === null) {
            return;
        }

        $localDateStr = $localDate->format('Y-m-d');

        $localTime = $observation->local_time;

        $sourceEnum = $observation->source;

        // Compute the 10-min window start
        $windowStart = ObservationWindowAggregator::windowStartFor($localDateStr, $localTime);

        // Upsert the window row
        // @phpstan-ignore-next-line argument.type
        $this->windowAggregator->recomputeWindow($site, $species, $windowStart, $sourceEnum);

        // Upsert the daily summary
        $tz = new \DateTimeZone($site->timezone);
        $date = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $localDateStr, $tz);

        // @phpstan-ignore-next-line notIdentical.alwaysTrue
        if ($date !== false) {
            // @phpstan-ignore-next-line argument.type
            $this->dailyAggregator->recomputeDay($site, $species, $date);
        }
    }

    /**
     * Resolve the site for an observation.
     * Uses the observation's site_id if set, otherwise falls back to the user's default site.
     */
    private function resolveSite(Observation $observation): ?Site
    {
        /** @var null|Site $site */
        $site = Site::query()->find($observation->site_id);

        if ($site !== null) {
            return $site;
        }

        // Fallback: user's first site
        /** @var null|Site $site */
        $site = Site::query()
            ->where('user_id', $observation->user_id)
            ->first();

        return $site;
    }
}
