<?php

declare(strict_types=1);

use App\Domain\Kitchen\KitchenSnapshot;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use App\Services\Kitchen\EloquentKitchenSnapshotReader;

\covers(EloquentKitchenSnapshotReader::class);
\covers(KitchenSnapshot::class);

\it('builds an ownership-safe kitchen snapshot', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Older', 'created_at' => '2026-09-01 08:00:00']);
    $latest = Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Latest', 'created_at' => '2026-09-10 08:00:00']);
    Recipe::factory()->create(['user_id' => $other->id, 'name' => 'Other']);

    ShoppingList::query()->create(['user_id' => $owner->id, 'name' => 'Milk', 'order' => 1, 'status' => 'active']);
    ShoppingList::query()->create(['user_id' => $owner->id, 'name' => '#Dairy', 'order' => 2, 'status' => 'active']);
    ShoppingList::query()->create(['user_id' => $owner->id, 'name' => 'Done', 'order' => 3, 'status' => 'checked']);
    ShoppingList::query()->create(['user_id' => $other->id, 'name' => 'Other', 'order' => 1, 'status' => 'active']);

    $snapshot = (new EloquentKitchenSnapshotReader())->read($owner->id);

    \expect($snapshot->recipes->count)->toBe(2)
        ->and($snapshot->recipes->latest?->name)->toBe('Latest')
        ->and($snapshot->recipes->latest?->id)->toBe($latest->id)
        ->and($snapshot->shoppingList->activeActionableCount)->toBe(1)
        ->and($snapshot->recentRecipes)->toHaveCount(2)
        ->and($snapshot->recentRecipes[0]->name)->toBe('Latest')
        ->and($snapshot->favouriteRecipes)->toHaveCount(0);
});

\it('returns an empty kitchen snapshot for a user without data', function (): void {
    $user = User::factory()->create();

    $snapshot = (new EloquentKitchenSnapshotReader())->read($user->id);

    \expect($snapshot->recipes->count)->toBe(0)
        ->and($snapshot->recipes->latest)->toBeNull()
        ->and($snapshot->shoppingList->activeActionableCount)->toBe(0)
        ->and($snapshot->recentRecipes)->toHaveCount(0)
        ->and($snapshot->favouriteRecipes)->toHaveCount(0);
});

\it('lists favourite recipes separately', function (): void {
    $owner = User::factory()->create();

    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Favourite', 'favourite' => true]);
    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Plain', 'favourite' => false]);

    $snapshot = (new EloquentKitchenSnapshotReader())->read($owner->id);

    \expect($snapshot->favouriteRecipes)->toHaveCount(1)
        ->and($snapshot->favouriteRecipes[0]->name)->toBe('Favourite');
});
