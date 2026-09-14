<?php

declare(strict_types=1);

namespace App\Services\Kitchen;

use App\Domain\Kitchen\Contracts\KitchenSnapshotReaderInterface;
use App\Domain\Kitchen\KitchenSnapshot;
use App\Domain\Overview\LatestRecipe;
use App\Domain\Overview\RecipeOverview;
use App\Domain\Overview\ShoppingListOverview;
use App\Models\Recipe;
use App\Models\ShoppingList;

final class EloquentKitchenSnapshotReader implements KitchenSnapshotReaderInterface
{
    public function read(int $userId): KitchenSnapshot
    {
        $latestRecipe = Recipe::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return new KitchenSnapshot(
            recipes: new RecipeOverview(
                count: Recipe::query()->where('user_id', $userId)->count(),
                latest: $latestRecipe instanceof Recipe ? new LatestRecipe(
                    id: $latestRecipe->id,
                    name: $latestRecipe->name,
                    createdAt: $this->toImmutable($latestRecipe->created_at),
                ) : null,
            ),
            shoppingList: new ShoppingListOverview(
                activeActionableCount: ShoppingList::query()
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where('name', 'not like', '#%')
                    ->count(),
            ),
        );
    }

    private function toImmutable(?\DateTimeInterface $date): \DateTimeImmutable
    {
        if ($date === null) {
            throw new \LogicException('Recipe created_at must be present.');
        }

        return \DateTimeImmutable::createFromInterface($date);
    }
}
