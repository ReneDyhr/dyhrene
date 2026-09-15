<?php

declare(strict_types=1);

namespace App\Services\Kitchen;

use App\Domain\Kitchen\Contracts\KitchenSnapshotReaderInterface;
use App\Domain\Kitchen\KitchenSnapshot;
use App\Domain\Kitchen\RecipeSummary;
use App\Domain\Overview\LatestRecipe;
use App\Domain\Overview\RecipeOverview;
use App\Domain\Overview\ShoppingListOverview;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\RecipeTag;
use App\Models\ShoppingList;
use Illuminate\Support\Collection;

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
            recentRecipes: $this->toSummaries(
                Recipe::query()
                    ->with(['categories', 'ingredients', 'tags'])
                    ->where('user_id', $userId)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(),
            ),
            favouriteRecipes: $this->toSummaries(
                Recipe::query()
                    ->with(['categories', 'ingredients', 'tags'])
                    ->where('user_id', $userId)
                    ->where('favourite', true)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(),
            ),
        );
    }

    /**
     * @param  Collection<int, Recipe> $recipes
     * @return list<RecipeSummary>
     */
    private function toSummaries(Collection $recipes): array
    {
        $summaries = [];

        foreach ($recipes as $recipe) {
            $ingredientCount = 0;

            foreach ($recipe->ingredients as $ingredient) {
                if (!\str_starts_with($ingredient->name, '#')) {
                    $ingredientCount++;
                }
            }

            $summaries[] = new RecipeSummary(
                id: $recipe->id,
                name: $recipe->name,
                categoryNames: \array_values(
                    $recipe->categories
                        ->map(static fn(Category $category): string => $category->name)
                        ->all(),
                ),
                ingredientCount: $ingredientCount,
                tagNames: \array_values(
                    $recipe->tags
                        ->map(static fn(RecipeTag $tag): string => $tag->name)
                        ->all(),
                ),
                createdAt: $this->toImmutable($recipe->created_at),
            );
        }

        return $summaries;
    }

    private function toImmutable(?\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date ?? \now());
    }
}
