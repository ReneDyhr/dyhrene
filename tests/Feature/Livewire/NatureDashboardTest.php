<?php

declare(strict_types=1);

use App\Enums\ObservationSourceEnum;
use App\Livewire\Nature\Dashboard;
use App\Models\BirdnetDetection;
use App\Models\DailySpeciesSummary;
use App\Models\Observation;
use App\Models\Site;
use App\Models\Species;
use App\Models\User;

\covers(Dashboard::class);

\it('redirects guests to login', function (): void {
    $this->get(\route('nature.dashboard'))
        ->assertRedirect(\route('login'));
});

\it('shows today species from daily summaries', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create([
        'common_name' => 'Common Blackbird',
        'scientific_name' => 'Turdus merula',
    ]);

    $today = \now('Europe/Copenhagen')->format('Y-m-d');

    DailySpeciesSummary::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'date' => $today,
        'windows_present' => 12,
        'records' => 500,
        'sources' => \json_encode([ObservationSourceEnum::Birdnet->value]),
        'last_seen_at' => $today . ' 06:30:00',
    ]);

    $this->actingAs($user)
        ->get(\route('nature.dashboard'))
        ->assertSee('Common Blackbird', false);
});

\it('shows audio player when detection has audio', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create([
        'common_name' => 'Song Thrush',
        'scientific_name' => 'Turdus philomelos',
    ]);

    $today = \now('Europe/Copenhagen')->format('Y-m-d');

    $observation = Observation::factory()
        ->for($species)
        ->for($user)
        ->for($site)
        ->create(['observed_at' => $today, 'source' => ObservationSourceEnum::Birdnet->value]);

    BirdnetDetection::factory()
        ->for($species)
        ->for($user)
        ->for($observation, 'observation')
        ->create(['audio_path' => 'detections/test-song.wav']);

    DailySpeciesSummary::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'date' => $today,
        'windows_present' => 5,
        'records' => 100,
        'sources' => \json_encode([ObservationSourceEnum::Birdnet->value]),
        'last_seen_at' => $today . ' 08:00:00',
    ]);

    $this->actingAs($user)
        ->get(\route('nature.dashboard'))
        ->assertSee('audio controls', false);
});

\it('shows no recording when no audio available', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create([
        'common_name' => 'Blue Tit',
        'scientific_name' => 'Cyanistes caeruleus',
    ]);

    $today = \now('Europe/Copenhagen')->format('Y-m-d');

    DailySpeciesSummary::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'date' => $today,
        'windows_present' => 1,
        'records' => 10,
        'sources' => \json_encode([ObservationSourceEnum::Manual->value]),
    ]);

    $this->actingAs($user)
        ->get(\route('nature.dashboard'))
        ->assertSee('No recording', false);
});

\it('shows source badges correctly', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);

    $species1 = Species::factory()->for($user)->create(['common_name' => 'Acoustic Bird']);
    $species2 = Species::factory()->for($user)->create(['common_name' => 'Manual Bird']);

    $today = \now('Europe/Copenhagen')->format('Y-m-d');

    DailySpeciesSummary::query()->create([
        'site_id' => $site->id,
        'species_id' => $species1->id,
        'date' => $today,
        'windows_present' => 1,
        'records' => 10,
        'sources' => \json_encode([ObservationSourceEnum::Birdnet->value]),
    ]);

    DailySpeciesSummary::query()->create([
        'site_id' => $site->id,
        'species_id' => $species2->id,
        'date' => $today,
        'windows_present' => 1,
        'records' => 1,
        'sources' => \json_encode([ObservationSourceEnum::Manual->value]),
    ]);

    $this->actingAs($user)
        ->get(\route('nature.dashboard'))
        ->assertSee('Acoustic Bird', false)
        ->assertSee('Manual Bird', false)
        ->assertSee('BirdNET', false)
        ->assertSee('Manual', false);
});

\it('hides rejected species', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create([
        'common_name' => 'Rejected Bird',
        'status' => App\Enums\SpeciesStatusEnum::Rejected->value,
    ]);

    $today = \now('Europe/Copenhagen')->format('Y-m-d');

    DailySpeciesSummary::query()->create([
        'site_id' => $site->id,
        'species_id' => $species->id,
        'date' => $today,
        'windows_present' => 1,
        'records' => 1,
        'sources' => \json_encode([ObservationSourceEnum::Birdnet->value]),
    ]);

    $this->actingAs($user)
        ->get(\route('nature.dashboard'))
        ->assertDontSee('Rejected Bird');
});
