<?php

declare(strict_types=1);

use App\Domain\Nature\ObservationLocalizer;
use App\Models\Site;

\covers(ObservationLocalizer::class);

\it('converts a UTC datetime to local Europe/Copenhagen', function (): void {
    $site = new Site([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $locale = new ObservationLocalizer();

    // July — UTC+2 (CEST): 10:00 UTC → 12:00 local
    $result = $locale->localize('2026-07-15', '10:00:00', $site);

    \expect($result['local_date'])->toBe('2026-07-15');
    \expect($result['local_time'])->toBe('12:00:00');
    \expect($result['day_of_year'])->toBe(195); // July 15 is ~day 196 (0-indexed: 195)
});

\it('handles late-night UTC that rolls to next local day', function (): void {
    $site = new Site([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $locale = new ObservationLocalizer();

    // July — UTC+2: 23:00 UTC → 01:00 next local day
    $result = $locale->localize('2026-07-15', '23:00:00', $site);

    \expect($result['local_date'])->toBe('2026-07-16');
    \expect($result['local_time'])->toBe('01:00:00');
});

\it('handles winter timezone (UTC+1) correctly', function (): void {
    $site = new Site([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $locale = new ObservationLocalizer();

    // January — UTC+1 (CET): 10:00 UTC → 11:00 local
    $result = $locale->localize('2026-01-15', '10:00:00', $site);

    \expect($result['local_date'])->toBe('2026-01-15');
    \expect($result['local_time'])->toBe('11:00:00');
});

\it('returns null local_time when no time is provided', function (): void {
    $site = new Site([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $locale = new ObservationLocalizer();

    $result = $locale->localize('2026-07-15', null, $site);

    \expect($result['local_date'])->toBe('2026-07-15');
    \expect($result['local_time'])->toBeNull();
});

\it('uses current time when no date is provided', function (): void {
    $site = new Site([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $locale = new ObservationLocalizer();

    $result = $locale->localize(null, null, $site);

    \expect($result['local_date'])->not()->toBeEmpty();
    \expect($result['local_time'])->toBeNull();
});

\it('returns a CarbonImmutable in the site timezone', function (): void {
    $site = new Site([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $locale = new ObservationLocalizer();

    $result = $locale->toLocalCarbon('2026-07-15', '10:00:00', $site);

    \expect($result->timezone->getName())->toBe('Europe/Copenhagen');
    \expect($result->format('H:i'))->toBe('12:00');
});
