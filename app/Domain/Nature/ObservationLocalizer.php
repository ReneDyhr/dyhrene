<?php

declare(strict_types=1);

namespace App\Domain\Nature;

use App\Models\Site;
use Carbon\CarbonImmutable;

/**
 * Converts a UTC observation datetime to local site time.
 *
 * Uniform UTC→local conversion for all observation sources.
 *
 * Framework-agnostic — receives primitives/models, returns primitives.
 */
final class ObservationLocalizer
{
    /**
     * Convert a UTC date+time to the site's local timezone.
     *
     * @return array{local_date: string, local_time: ?string, day_of_year: int}
     */
    public function localize(?string $observedAt, ?string $observedTime, Site $site): array
    {
        if ($observedAt === null || $observedAt === '') {
            $now = CarbonImmutable::now('UTC');

            return [
                'local_date' => $now->setTimezone($site->timezone)->format('Y-m-d'),
                'local_time' => null,
                'day_of_year' => (int) $now->setTimezone($site->timezone)->format('z'),
            ];
        }

        // Combine date and time (or default to midnight UTC)
        $timePart = $observedTime !== null && $observedTime !== '' ? $observedTime : '00:00:00';
        $utcString = "{$observedAt} {$timePart}";

        $utcTime = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $utcString, 'UTC')
            ?: CarbonImmutable::now('UTC');

        $localTime = $utcTime->setTimezone($site->timezone);

        return [
            'local_date' => $localTime->format('Y-m-d'),
            'local_time' => $observedTime !== null && $observedTime !== '' ? $localTime->format('H:i:s') : null,
            'day_of_year' => (int) $localTime->format('z'),
        ];
    }

    /**
     * Convert a UTC date+time to a CarbonImmutable in the site timezone.
     */
    public function toLocalCarbon(?string $observedAt, ?string $observedTime, Site $site): CarbonImmutable
    {
        $localized = $this->localize($observedAt, $observedTime, $site);
        $timePart = $localized['local_time'] ?? '00:00:00';

        return CarbonImmutable::parse("{$localized['local_date']} {$timePart}", $site->timezone);
    }
}
