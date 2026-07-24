<?php

declare(strict_types=1);

use App\Domain\Nature\SolarCalculator;
use Carbon\CarbonImmutable;

\covers(SolarCalculator::class);

\it('returns sunrise time for a known location', function (): void {
    $calc = new SolarCalculator();
    // Use UTC noon to ensure we're on the right day regardless of timezone
    $date = CarbonImmutable::parse('2026-07-15 12:00:00', 'UTC');

    $sunrise = $calc->sunrise($date, 55.38, 9.15);

    \expect($sunrise)->not()->toBeNull();
    \expect((int) $sunrise->format('H'))->toBeLessThan(6); // Before 06:00 UTC in July
});

\it('returns sunset time for a known location', function (): void {
    $calc = new SolarCalculator();
    $date = CarbonImmutable::parse('2026-07-15 12:00:00', 'UTC');

    $sunset = $calc->sunset($date, 55.38, 9.15);

    \expect($sunset)->not()->toBeNull();
    \expect((int) $sunset->format('H'))->toBeGreaterThan(18); // After 18:00 UTC in July
});

\it('returns minutes from sunrise — positive after sunrise', function (): void {
    $calc = new SolarCalculator();
    // July sunrise at Jels is ~03:22 UTC; 08:00 UTC is well after
    $date = CarbonImmutable::parse('2026-07-15 08:00:00', 'UTC');

    $minutes = $calc->minutesFromSunrise($date, 55.38, 9.15);

    \expect($minutes)->not()->toBeNull();
    \expect($minutes)->toBeGreaterThan(0);
});

\it('returns negative minutes from sunrise before dawn', function (): void {
    $calc = new SolarCalculator();
    // July sunrise at Jels is ~03:22 UTC; 01:00 UTC is before
    $date = CarbonImmutable::parse('2026-07-15 01:00:00', 'UTC');

    $minutes = $calc->minutesFromSunrise($date, 55.38, 9.15);

    \expect($minutes)->not()->toBeNull();
    \expect($minutes)->toBeLessThan(0);
});

\it('returns minutes from sunset', function (): void {
    $calc = new SolarCalculator();
    // July sunset at Jels is ~19:37 UTC; 22:00 UTC is after
    $date = CarbonImmutable::parse('2026-07-15 22:00:00', 'UTC');

    $minutes = $calc->minutesFromSunset($date, 55.38, 9.15);

    \expect($minutes)->not()->toBeNull();
    \expect($minutes)->toBeGreaterThan(0);
});

\it('winter sunrise is much later than summer sunrise', function (): void {
    $calc = new SolarCalculator();
    $summer = CarbonImmutable::parse('2026-06-21', 'Europe/Copenhagen');
    $winter = CarbonImmutable::parse('2026-12-21', 'Europe/Copenhagen');

    $summerSunrise = $calc->sunrise($summer, 55.38, 9.15);
    $winterSunrise = $calc->sunrise($winter, 55.38, 9.15);

    \expect($summerSunrise)->not()->toBeNull();
    \expect($winterSunrise)->not()->toBeNull();
    \expect((int) $summerSunrise->format('H'))->toBeLessThan((int) $winterSunrise->format('H'));
});
