<?php

declare(strict_types=1);

use App\Livewire\Species\ObservationsIndex;
use App\Models\BirdnetDetection;
use App\Models\Observation;
use App\Models\Species;
use App\Models\User;
use Livewire\Livewire;

\covers(ObservationsIndex::class);

\it('loads the observations page for an authenticated user', function (): void {
    $user = User::factory()->create();
    $species = Species::factory()->for($user)->create(['common_name' => 'Eurasian Blackbird']);
    Observation::factory()
        ->for($species)
        ->for($user)
        ->create(['observed_at' => '2025-01-15']);

    Livewire::actingAs($user)
        ->test(ObservationsIndex::class)
        ->assertSee('All Observations')
        ->assertSee('Eurasian Blackbird')
        ->assertSee('15 Jan 2025');
});

\it('redirects guests to login', function (): void {
    $this->get(\route('observations.index'))
        ->assertRedirect(\route('login'));
});

\it('shows observations with species names', function (): void {
    $user = User::factory()->create();
    $sparrow = Species::factory()->for($user)->create(['common_name' => 'House Sparrow']);
    $robin = Species::factory()->for($user)->create(['common_name' => 'European Robin']);

    Observation::factory()
        ->for($sparrow)
        ->for($user)
        ->create(['observed_at' => '2025-03-01']);
    Observation::factory()
        ->for($robin)
        ->for($user)
        ->create(['observed_at' => '2025-03-02']);

    Livewire::actingAs($user)
        ->test(ObservationsIndex::class)
        ->assertSee('House Sparrow')
        ->assertSee('European Robin');
});

\it('shows audio player when a birdnet detection has audio', function (): void {
    $user = User::factory()->create();
    $species = Species::factory()->for($user)->create(['common_name' => 'Song Thrush']);
    $observation = Observation::factory()
        ->for($species)
        ->for($user)
        ->create(['observed_at' => '2025-04-01']);

    BirdnetDetection::factory()
        ->for($species)
        ->for($user)
        ->for($observation, 'observation')
        ->create(['audio_path' => 'detections/test-audio.wav']);

    Livewire::actingAs($user)
        ->test(ObservationsIndex::class)
        ->assertSee('audio controls');
});

\it('shows em dash when no audio is available', function (): void {
    $user = User::factory()->create();
    $species = Species::factory()->for($user)->create(['common_name' => 'Blue Tit']);
    $observation = Observation::factory()
        ->for($species)
        ->for($user)
        ->create(['observed_at' => '2025-05-01']);

    BirdnetDetection::factory()
        ->for($species)
        ->for($user)
        ->for($observation, 'observation')
        ->create(['audio_path' => null]);

    Livewire::actingAs($user)
        ->test(ObservationsIndex::class)
        ->assertDontSee('audio controls');
});

\it('shows delete button for each observation', function (): void {
    $user = User::factory()->create();
    $species = Species::factory()->for($user)->create();
    Observation::factory()
        ->for($species)
        ->for($user)
        ->create(['observed_at' => '2025-06-01']);

    Livewire::actingAs($user)
        ->test(ObservationsIndex::class)
        ->assertSee('fa-trash')
        ->assertSee('Delete observation');
});

\it('shows pagination when there are more than 50 observations', function (): void {
    $user = User::factory()->create();
    $species = Species::factory()->for($user)->create();

    // Create 51 observations to trigger pagination
    for ($i = 1; $i <= 51; $i++) {
        Observation::factory()
            ->for($species)
            ->for($user)
            ->create(['observed_at' => '2025-01-' . \str_pad((string) \min($i, 28), 2, '0', \STR_PAD_LEFT)]);
    }

    $test = Livewire::actingAs($user)
        ->test(ObservationsIndex::class);

    $test->assertViewHas('observations', function ($paginator): bool {
        return $paginator->total() > 50
            && $paginator->perPage() === 50
            && $paginator->hasPages();
    });
});
