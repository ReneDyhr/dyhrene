<?php

declare(strict_types=1);

namespace App\Domain\Nature;

use Carbon\CarbonImmutable;

/**
 * Pure solar position calculator.
 *
 * Uses PHP's built-in date_sun_info() which implements the
 * same algorithm as the NOAA Solar Calculator.
 *
 * Framework-agnostic — no Eloquent, no facades, no HTTP.
 */
final class SolarCalculator
{
    /**
     * Get sunrise time for a given date and location.
     */
    public function sunrise(CarbonImmutable $date, float $latitude, float $longitude): ?CarbonImmutable
    {
        $info = $this->sunInfo($date, $latitude, $longitude);

        if (!\is_int($info['sunrise'])) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp($info['sunrise'], $date->timezone);
    }

    /**
     * Get sunset time for a given date and location.
     */
    public function sunset(CarbonImmutable $date, float $latitude, float $longitude): ?CarbonImmutable
    {
        $info = $this->sunInfo($date, $latitude, $longitude);

        if (!\is_int($info['sunset'])) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp($info['sunset'], $date->timezone);
    }

    /**
     * Minutes from sunrise for a given datetime. Positive = after sunrise.
     * Returns null when sunrise is not available (e.g. polar regions).
     */
    public function minutesFromSunrise(CarbonImmutable $dateTime, float $latitude, float $longitude): ?int
    {
        $sunrise = $this->sunrise($dateTime, $latitude, $longitude);

        if ($sunrise === null) {
            return null;
        }

        return $this->diffInMinutes($dateTime, $sunrise);
    }

    /**
     * Minutes from sunset for a given datetime. Positive = after sunset.
     * Returns null when sunset is not available.
     */
    public function minutesFromSunset(CarbonImmutable $dateTime, float $latitude, float $longitude): ?int
    {
        $sunset = $this->sunset($dateTime, $latitude, $longitude);

        if ($sunset === null) {
            return null;
        }

        return $this->diffInMinutes($dateTime, $sunset);
    }

    /**
     * @return array{sunrise: bool|int, sunset: bool|int, transit: bool|int, civil_twilight_begin: bool|int, civil_twilight_end: bool|int, nautical_twilight_begin: bool|int, nautical_twilight_end: bool|int, astronomical_twilight_begin: bool|int, astronomical_twilight_end: bool|int}
     */
    private function sunInfo(CarbonImmutable $date, float $latitude, float $longitude): array
    {
        /** @var array{sunrise: bool|int, sunset: bool|int, transit: bool|int, civil_twilight_begin: bool|int, civil_twilight_end: bool|int, nautical_twilight_begin: bool|int, nautical_twilight_end: bool|int, astronomical_twilight_begin: bool|int, astronomical_twilight_end: bool|int} $info */
        $info = \date_sun_info($date->getTimestamp(), $latitude, $longitude);

        return $info;
    }

    private function diffInMinutes(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $diffSeconds = $from->diffInSeconds($to, false);

        return (int) \round($diffSeconds / 60.0);
    }
}
