<?php

declare(strict_types=1);

use App\Models\User;

\covers(App\Livewire\Overview\Index::class);
\covers(App\Livewire\Kitchen\Index::class);
\covers(App\Livewire\Workshop\Index::class);
\covers(App\Livewire\Household\Index::class);
\covers(App\Livewire\Family\Index::class);
\covers(App\Livewire\Recipes::class);

\it('redirects guests from every domain landing route', function (string $routeName): void {
    $this->get(\route($routeName))->assertRedirect(\route('login'));
})->with([
    'overview' => 'index',
    'kitchen' => 'kitchen.index',
    'recipes' => 'recipes.index',
    'workshop' => 'workshop.index',
    'household' => 'household.index',
    'family' => 'family.index',
]);

\it('renders each domain landing in its selected navigation area', function (string $routeName, string $area): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(\route($routeName))
        ->assertOk()
        ->assertSee('data-area="' . $area . '"', false);
})->with([
    'overview' => ['index', 'overview'],
    'kitchen' => ['kitchen.index', 'kitchen'],
    'workshop' => ['workshop.index', 'workshop'],
    'household' => ['household.index', 'household'],
    'family' => ['family.index', 'family'],
]);

\it('serves the overview at the root and keeps the recipe index deep-linked', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(\route('index'))
        ->assertOk()
        ->assertSee('Oversigt')
        ->assertSee('href="' . \route('recipes.index') . '"', false);

    $this->actingAs($user)->get(\route('recipes.index'))
        ->assertOk()
        ->assertSee('recipe-list', false);
});

\it('exposes expected quick links on each domain landing', function (string $routeName, string $expectedRouteName): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(\route($routeName))
        ->assertOk()
        ->assertSee('href="' . \route($expectedRouteName) . '"', false);
})->with([
    'kitchen' => ['kitchen.index', 'shopping.list'],
    'workshop' => ['workshop.index', 'print-jobs.index'],
    'household' => ['household.index', 'inventory.index'],
    'family' => ['family.index', 'mail.inbox'],
]);
