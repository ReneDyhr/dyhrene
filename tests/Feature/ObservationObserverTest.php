<?php

declare(strict_types=1);

use App\Enums\ObservationSourceEnum;
use App\Models\DailySpeciesSummary;
use App\Models\Observation;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use App\Models\User;
use App\Observers\ObservationObserver;

\covers(ObservationObserver::class);

\it('sets local_date and local_time on observation creation', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $species = Species::factory()->for($user)->create();

    $obs = Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => $site->id,
        'observed_at' => '2026-07-15',
        'observed_time' => '10:00:00',
        'location' => '55.38, 9.15',
        'location_raw' => '55.38, 9.15',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    \expect($obs->local_date)->not()->toBeNull();
    \expect($obs->local_date->format('Y-m-d'))->toBe('2026-07-15');
    \expect($obs->local_time)->not()->toBeNull();
    // 10:00 UTC → 12:00 CEST in July
    \expect($obs->local_time)->toBe('12:00:00');
});

\it('sets solar columns on observation creation', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $species = Species::factory()->for($user)->create();

    $obs = Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => $site->id,
        'observed_at' => '2026-07-15',
        'observed_time' => '10:00:00',
        'location' => '55.38, 9.15',
        'location_raw' => '55.38, 9.15',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    \expect($obs->minutes_from_sunrise)->not()->toBeNull();
    \expect($obs->minutes_from_sunset)->not()->toBeNull();
    \expect($obs->day_of_year)->not()->toBeNull();
});

\it('creates observation_windows row on observation create', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $species = Species::factory()->for($user)->create();

    Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => $site->id,
        'observed_at' => '2026-07-15',
        'observed_time' => '10:05:00',
        'location' => '55.38, 9.15',
        'location_raw' => '55.38, 9.15',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    \expect(ObservationWindow::query()->count())->toBeGreaterThan(0);
});

\it('creates daily_species_summary on observation create', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $species = Species::factory()->for($user)->create();

    Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => $site->id,
        'observed_at' => '2026-07-15',
        'observed_time' => '10:05:00',
        'location' => '55.38, 9.15',
        'location_raw' => '55.38, 9.15',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    \expect(DailySpeciesSummary::query()->count())->toBeGreaterThan(0);
});

\it('removes rollup rows on observation delete', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $species = Species::factory()->for($user)->create();

    $obs = Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => $site->id,
        'observed_at' => '2026-07-15',
        'observed_time' => '10:05:00',
        'location' => '55.38, 9.15',
        'location_raw' => '55.38, 9.15',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    $windowCount = ObservationWindow::query()->count();
    $summaryCount = DailySpeciesSummary::query()
        ->where('species_id', $species->id)
        ->where('date', '2026-07-15')
        ->count();

    \expect($windowCount)->toBeGreaterThan(0);
    \expect($summaryCount)->toBeGreaterThan(0);

    $obs->delete();

    \expect(ObservationWindow::query()->count())->toBe(0);
    \expect(DailySpeciesSummary::query()
        ->where('species_id', $species->id)
        ->where('date', '2026-07-15')
        ->count())->toBe(0);
});

\it('assigns default site when site_id is null', function (): void {
    $user = User::factory()->create();
    Site::factory()->for($user)->create([
        'timezone' => 'Europe/Copenhagen',
        'latitude' => 55.38,
        'longitude' => 9.15,
    ]);
    $species = Species::factory()->for($user)->create();

    $obs = Observation::query()->create([
        'species_id' => $species->id,
        'user_id' => $user->id,
        'site_id' => null,
        'observed_at' => '2026-07-15',
        'observed_time' => '10:00:00',
        'source' => ObservationSourceEnum::Birdnet->value,
    ]);

    \expect($obs->fresh()->site_id)->not()->toBeNull();
});
