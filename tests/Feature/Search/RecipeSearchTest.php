<?php

declare(strict_types=1);

use App\Domain\Search\RecipeSearch;
use App\Domain\Search\SearchQuery;
use App\Models\Recipe;
use App\Models\User;

\covers(RecipeSearch::class);
\covers(SearchQuery::class);

\it('searches recipes by name for the owning user only', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Pasta Carbonara']);
    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Pizza Margherita']);
    Recipe::factory()->create(['user_id' => $other->id, 'name' => 'Pasta Bolognese']);

    $results = (new RecipeSearch())->searchFor($owner, new SearchQuery('pasta'));

    \expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Pasta Carbonara');
});

\it('matches partially and orders newest first', function (): void {
    $owner = User::factory()->create();

    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Tomato Soup', 'created_at' => '2026-09-01 08:00:00']);
    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Tomato Salad', 'created_at' => '2026-09-10 08:00:00']);

    $results = (new RecipeSearch())->searchFor($owner, new SearchQuery('tomato'));

    \expect($results)->toHaveCount(2)
        ->and($results[0]->name)->toBe('Tomato Salad');
});

\it('returns no results for an empty or whitespace-only query', function (): void {
    $owner = User::factory()->create();

    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Pasta']);

    \expect((new RecipeSearch())->searchFor($owner, new SearchQuery('   ')))->toBe([])
        ->and((new RecipeSearch())->searchFor($owner, new SearchQuery('')))->toBe([]);
});
