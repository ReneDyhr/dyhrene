<?php

declare(strict_types=1);

use App\Enums\ObservationSourceEnum;
use App\Livewire\Nature\StationOverview;
use App\Models\DailySpeciesSummary;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use App\Models\User;
use Livewire\Livewire;

\covers(StationOverview::class);

\it('redirects guests to login', function (): void {
    $this->get(\route('nature.station'))
        ->assertRedirect(\route('login'));
});

\it('renders the station overview page', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create(['common_name' => 'Test Bird']);

    DailySpeciesSummary::factory()
        ->for($site)
        ->for($species)
        ->create([
            'date' => \now('Europe/Copenhagen')->format('Y-m-d'),
            'windows_present' => 5,
            'records' => 100,
            'sources' => \json_encode([ObservationSourceEnum::Birdnet->value]),
        ]);

    Livewire::actingAs($user)
        ->test(StationOverview::class)
        ->assertSee('Station Overview')
        ->assertSee('Species Accumulation')
        ->assertSee('Source Breakdown');
});

\it('shows month view when a month is selected', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create(['common_name' => 'Test Bird']);

    DailySpeciesSummary::factory()
        ->for($site)
        ->for($species)
        ->create([
            'date' => \now('Europe/Copenhagen')->format('Y') . '-01-15',
            'windows_present' => 10,
            'records' => 200,
            'sources' => \json_encode([ObservationSourceEnum::Birdnet->value]),
        ]);

    Livewire::actingAs($user)
        ->test(StationOverview::class)
        ->call('selectMonth', 1)
        ->assertSee('Test Bird')
        ->assertSee('10');
});

\it('shows source breakdown', function (): void {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create(['timezone' => 'Europe/Copenhagen']);
    $species = Species::factory()->for($user)->create(['common_name' => 'Source Bird']);

    ObservationWindow::factory()
        ->for($site)
        ->for($species)
        ->create([
            'source' => ObservationSourceEnum::Birdnet->value,
            'records' => 50,
        ]);

    Livewire::actingAs($user)
        ->test(StationOverview::class)
        ->assertSee('BirdNET');
});

\it('clears month selection', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(StationOverview::class)
        ->call('selectMonth', 6)
        ->assertSet('selectedMonth', 6)
        ->call('clearMonth')
        ->assertSet('selectedMonth', null);
});
