<?php

declare(strict_types=1);

use App\Models\Recipe;
use App\Models\User;

\covers(App\Livewire\Recipes::class);

\it('renders the recipe list when a recipe has a null created_at', function (): void {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id, 'name' => 'Arvelig opskrift']);
    Recipe::query()->whereKey($recipe->id)->update(['created_at' => null]);

    $this->actingAs($user)
        ->get(\route('recipes.index'))
        ->assertOk()
        ->assertSee('Arvelig opskrift');
});
