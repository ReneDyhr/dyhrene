<?php

declare(strict_types=1);

namespace App\Domain\Nature;

use App\Models\Site;
use Carbon\CarbonImmutable;

/**
 * Computes solar-relative columns for an observation.
 *
 * Uses the site's authoritative coordinates, never per-detection coords.
 *
 * Framework-agnostic — receives primitives/models, returns primitives.
 */
final class ObservationSolarizer
{
    private SolarCalculator $solarCalculator;

    public function __construct()
    {
        $this->solarCalculator = new SolarCalculator();
    }

    /**
     * Compute solar-relative values for an observation.
     *
     * @return array{minutes_from_sunrise: ?int, minutes_from_sunset: ?int}
     */
    public function solarize(
        CarbonImmutable $localDateTime,
        Site $site,
    ): array {
        $lat = (float) $site->latitude; // @phpstan-ignore cast.useless
        $lon = (float) $site->longitude; // @phpstan-ignore cast.useless

        return [
            'minutes_from_sunrise' => $this->solarCalculator->minutesFromSunrise($localDateTime, $lat, $lon),
            'minutes_from_sunset' => $this->solarCalculator->minutesFromSunset($localDateTime, $lat, $lon),
        ];
    }
}
