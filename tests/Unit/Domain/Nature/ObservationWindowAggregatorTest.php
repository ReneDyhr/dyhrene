<?php

declare(strict_types=1);

use App\Domain\Nature\ObservationWindowAggregator;
use App\Enums\ObservationSourceEnum;
use App\Models\Observation;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use App\Models\User;

\covers(ObservationWindowAggregator::class);

\it('creates a window when observations exist', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    $localDate = '2026-07-15';
    $localTime = '12:05:00';

    Observation::factory()
        ->for($species)
        ->for($user)
        ->for($site)
        ->create([
            'observed_at' => $localDate,
            'observed_time' => '10:05:00',
            'local_date' => $localDate,
            'local_time' => $localTime,
            'source' => ObservationSourceEnum::Birdnet->value,
        ]);

    $aggregator = new ObservationWindowAggregator();
    $windowStart = ObservationWindowAggregator::windowStartFor($localDate, $localTime);

    $aggregator->recomputeWindow($site, $species, $windowStart, ObservationSourceEnum::Birdnet);

    \expect(ObservationWindow::query()->count())->toBe(1);
    $window = ObservationWindow::query()->first();
    \expect($window->records)->toBe(1);
});

\it('removes window row when no observations remain in that window', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    // Create a window row manually, then recompute with no observations
    ObservationWindow::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'window_start' => '2026-07-15 12:00:00',
        'source' => ObservationSourceEnum::Birdnet->value,
        'records' => 5,
    ]);

    $aggregator = new ObservationWindowAggregator();
    $windowStart = Carbon\CarbonImmutable::parse('2026-07-15 12:00:00', 'Europe/Copenhagen');

    // No observations in this window — should delete
    $aggregator->recomputeWindow($site, $species, $windowStart, ObservationSourceEnum::Birdnet);

    \expect(ObservationWindow::query()->count())->toBe(0);
});

\it('upserts existing window row', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    Observation::factory()
        ->for($species)
        ->for($user)
        ->for($site)
        ->create([
            'observed_at' => '2026-07-15',
            'observed_time' => '10:00:00',
            'local_date' => '2026-07-15',
            'local_time' => '12:00:00',
            'source' => ObservationSourceEnum::Birdnet->value,
        ]);

    $aggregator = new ObservationWindowAggregator();
    $windowStart = Carbon\CarbonImmutable::parse('2026-07-15 12:00:00', 'Europe/Copenhagen');

    // First call creates
    $aggregator->recomputeWindow($site, $species, $windowStart, ObservationSourceEnum::Birdnet);
    \expect(ObservationWindow::query()->count())->toBe(1);
    \expect(ObservationWindow::query()->first()->records)->toBe(1);

    // Second call updates — idempotent
    $aggregator->recomputeWindow($site, $species, $windowStart, ObservationSourceEnum::Birdnet);
    \expect(ObservationWindow::query()->count())->toBe(1);
});

\it('floors to 10-minute window boundary', function (): void {
    $windowStart = ObservationWindowAggregator::windowStartFor('2026-07-15', '12:07:30');

    \expect($windowStart->format('H:i'))->toBe('12:00');
});

\it('separates windows by source', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    Observation::factory()
        ->for($species)
        ->for($user)
        ->for($site)
        ->create([
            'observed_at' => '2026-07-15',
            'observed_time' => '10:00:00',
            'local_date' => '2026-07-15',
            'local_time' => '12:00:00',
            'source' => ObservationSourceEnum::Birdnet->value,
        ]);

    Observation::factory()
        ->for($species)
        ->for($user)
        ->for($site)
        ->create([
            'observed_at' => '2026-07-15',
            'observed_time' => '10:02:00',
            'local_date' => '2026-07-15',
            'local_time' => '12:02:00',
            'source' => ObservationSourceEnum::Manual->value,
        ]);

    $aggregator = new ObservationWindowAggregator();
    $windowStart = Carbon\CarbonImmutable::parse('2026-07-15 12:00:00', 'Europe/Copenhagen');

    $aggregator->recomputeWindow($site, $species, $windowStart, ObservationSourceEnum::Birdnet);
    $aggregator->recomputeWindow($site, $species, $windowStart, ObservationSourceEnum::Manual);

    \expect(ObservationWindow::query()->count())->toBe(2);
});
