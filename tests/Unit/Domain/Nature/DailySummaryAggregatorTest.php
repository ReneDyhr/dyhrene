<?php

declare(strict_types=1);

use App\Domain\Nature\DailySummaryAggregator;
use App\Enums\ObservationSourceEnum;
use App\Models\DailySpeciesSummary;
use App\Models\Observation;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use App\Models\User;

\covers(DailySummaryAggregator::class);

\it('creates a daily summary from observation windows', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => $site->id,
        'observed_at' => '2026-07-15',
        'observed_time' => '10:00:00',
        'local_date' => '2026-07-15',
        'local_time' => '12:00:00',
        'location' => '55.38, 9.15',
        'location_raw' => '55.38, 9.15',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    ObservationWindow::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'window_start' => '2026-07-15 12:00:00',
        'source' => ObservationSourceEnum::Birdnet->value,
        'records' => 10,
    ]);

    $aggregator = new DailySummaryAggregator();
    $date = Carbon\CarbonImmutable::parse('2026-07-15', 'Europe/Copenhagen');

    $aggregator->recomputeDay($site, $species, $date);

    \expect(DailySpeciesSummary::query()->count())->toBe(1);
    $summary = DailySpeciesSummary::query()->first();
    \expect($summary->windows_present)->toBe(1);
    \expect($summary->records)->toBe(10);
    \expect($summary->sources_array)->toContain(ObservationSourceEnum::Birdnet->value);
});

\it('deletes summary when no windows remain', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    DailySpeciesSummary::query()->create([
        'site_id' => $site->id,
        'date' => '2026-07-15',
        'species_id' => $species->id,
        'windows_present' => 5,
        'records' => 100,
        'sources' => \json_encode([ObservationSourceEnum::Birdnet->value]),
    ]);

    $aggregator = new DailySummaryAggregator();
    $date = Carbon\CarbonImmutable::parse('2026-07-15', 'Europe/Copenhagen');

    // No windows — should delete the summary
    $aggregator->recomputeDay($site, $species, $date);

    \expect(DailySpeciesSummary::query()->count())->toBe(0);
});

\it('aggregates multiple windows for the same day', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => $site->id,
        'observed_at' => '2026-07-15',
        'observed_time' => '06:00:00',
        'local_date' => '2026-07-15',
        'local_time' => '08:00:00',
        'location' => '55.38, 9.15',
        'location_raw' => '55.38, 9.15',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    ObservationWindow::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'window_start' => '2026-07-15 08:00:00',
        'source' => ObservationSourceEnum::Birdnet->value,
        'records' => 5,
    ]);

    ObservationWindow::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'window_start' => '2026-07-15 09:00:00',
        'source' => ObservationSourceEnum::Birdnet->value,
        'records' => 3,
    ]);

    $aggregator = new DailySummaryAggregator();
    $date = Carbon\CarbonImmutable::parse('2026-07-15', 'Europe/Copenhagen');

    $aggregator->recomputeDay($site, $species, $date);

    $summary = DailySpeciesSummary::query()->first();
    \expect($summary->windows_present)->toBe(2);
    \expect($summary->records)->toBe(8);
});
