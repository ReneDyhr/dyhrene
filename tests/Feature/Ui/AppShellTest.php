<?php

declare(strict_types=1);

use App\Enums\AppArea;
use App\Livewire\Nature\Dashboard;
use App\Models\User;
use App\Support\Navigation\AppNavigation;
use Livewire\Livewire;

\covers(AppArea::class);
\covers(AppNavigation::class);
\covers(Dashboard::class);

\it('maps only exact routes and dotted route namespaces to application areas', function (): void {
    $navigation = new AppNavigation();

    \expect($navigation->areaForRoute('index'))->toBe(AppArea::Overview)
        ->and($navigation->areaForRoute('storage'))->toBe(AppArea::Kitchen)
        ->and($navigation->areaForRoute('storage.units'))->toBe(AppArea::Overview)
        ->and($navigation->areaForRoute('shopping.list'))->toBe(AppArea::Kitchen)
        ->and($navigation->areaForRoute('shopping'))->toBe(AppArea::Overview)
        ->and($navigation->areaForRoute('kitchen.index'))->toBe(AppArea::Kitchen)
        ->and($navigation->areaForRoute('recipes.index'))->toBe(AppArea::Kitchen)
        ->and($navigation->areaForRoute('workshop.index'))->toBe(AppArea::Workshop)
        ->and($navigation->areaForRoute('household.index'))->toBe(AppArea::Household)
        ->and($navigation->areaForRoute('family.index'))->toBe(AppArea::Family)
        ->and($navigation->areaForRoute('nature.dashboard'))->toBe(AppArea::Nature)
        ->and($navigation->areaForRoute('nature'))->toBe(AppArea::Overview);
});

\it('uses the six stable entry routes for the navigation areas', function (): void {
    $navigation = new AppNavigation();
    $itemsByArea = \collect($navigation->items(AppArea::Overview))->keyBy(
        static fn(array $item): string => $item['area']->value,
    );

    \expect($itemsByArea['overview']['href'])->toBe(\route('index'))
        ->and($itemsByArea['kitchen']['href'])->toBe(\route('kitchen.index'))
        ->and($itemsByArea['nature']['href'])->toBe(\route('nature.dashboard'))
        ->and($itemsByArea['workshop']['href'])->toBe(\route('workshop.index'))
        ->and($itemsByArea['household']['href'])->toBe(\route('household.index'))
        ->and($itemsByArea['family']['href'])->toBe(\route('family.index'));
});

\it('renders the shell safely without an authenticated user', function (): void {
    $html = Illuminate\Support\Facades\Blade::render('<x-layouts.app-shell><p>Indhold</p></x-layouts.app-shell>');

    \expect($html)->toContain('>Gæst</span>')
        ->toContain('href="#main-content"')
        ->toContain('<main id="main-content"');
});

\it('renders the authenticated nature screen in the accessible application shell', function (): void {
    $user = User::factory()->create(['name' => 'René Dyhr']);

    $response = $this->actingAs($user)->get(\route('nature.dashboard'));

    $response->assertOk();
    $response->assertSee($user->name);
    $response->assertSee('aria-label="Primær navigation"', false);
    $response->assertSee('aria-label="Mobil navigation"', false);
    $response->assertSee('data-area="nature"', false);
    $response->assertSee('aria-label="Opskriftssøgning"', false);
    $response->assertSee('href="#main-content"', false);
    $response->assertSee('<main id="main-content"', false);
    \expect($response->getContent())->toMatch('/<a\\b(?=[^>]*data-area="nature")(?=[^>]*aria-current="page")[^>]*>/');
    \expect(\substr_count($response->getContent(), 'aria-label="Primær navigation"'))->toBe(1)
        ->and(\substr_count($response->getContent(), 'id="main-content"'))->toBe(1);
});

\it('preserves the nature navigation state during a Livewire update', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class)
        ->set('date', '2026-09-14');

    \expect($component->html())
        ->toContain('data-area="nature"')
        ->toMatch('/<a\\b(?=[^>]*data-area="nature")(?=[^>]*aria-current="page")[^>]*>/');
});

\it('redirects guests from the protected nature shell entry route', function (): void {
    $this->get(\route('nature.dashboard'))
        ->assertRedirect(\route('login'));
});
