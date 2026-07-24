<?php

declare(strict_types=1);

use App\Console\Commands\BackfillNatureDataCommand;
use App\Models\Observation;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

\covers(BackfillNatureDataCommand::class);

\it('backfills site_id and location_raw for observations', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    $obs = Observation::factory()
        ->for($species)
        ->for($user)
        ->create([
            'site_id' => null,
            'observed_at' => '2026-07-15',
            'observed_time' => '10:00:00',
            'location' => '55.6761, 12.5683',
            'location_raw' => null,
            'local_date' => null,
            'source' => 'birdnet',
        ]);

    Artisan::call('nature:backfill');

    $obs->refresh();

    \expect($obs->site_id)->not()->toBeNull();
    \expect($obs->location_raw)->not()->toBeNull();
});

\it('recomputes local columns', function (): void {
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
            'local_date' => null,
            'local_time' => null,
            'source' => 'birdnet',
        ]);

    Artisan::call('nature:backfill');

    $obs = Observation::query()->whereNotNull('local_date')->first();

    \expect($obs)->not()->toBeNull();
    \expect($obs->local_date->format('Y-m-d'))->toBe('2026-07-15');
    \expect($obs->local_time)->not()->toBeNull();
});

\it('rebuilds observation windows', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    Observation::factory()
        ->for($species)
        ->for($user)
        ->for($site)
        ->create([
            'observed_at' => '2026-07-15',
            'observed_time' => '10:05:00',
            'local_date' => '2026-07-15',
            'local_time' => '12:05:00',
            'source' => 'birdnet',
        ]);

    Artisan::call('nature:backfill');

    \expect(ObservationWindow::query()->count())->toBeGreaterThan(0);
});

\it('is idempotent — running twice does not duplicate data', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create();

    Observation::factory()
        ->for($species)
        ->for($user)
        ->for($site)
        ->create([
            'observed_at' => '2026-07-15',
            'observed_time' => '10:05:00',
            'local_date' => '2026-07-15',
            'local_time' => '12:05:00',
            'source' => 'birdnet',
        ]);

    Artisan::call('nature:backfill');
    $firstCount = ObservationWindow::query()->count();

    Artisan::call('nature:backfill');
    $secondCount = ObservationWindow::query()->count();

    \expect($secondCount)->toBe($firstCount);
});
