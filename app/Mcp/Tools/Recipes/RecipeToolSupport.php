<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Models\Category;
use App\Models\Recipe;

final class RecipeToolSupport
{
    public static function isHeaderIngredient(string $ingredient): bool
    {
        return \str_starts_with(\trim($ingredient), '#');
    }

    public static function normalizeHeaderTitle(string $ingredient): string
    {
        $value = \trim($ingredient);

        if (!self::isHeaderIngredient($value)) {
            return $value;
        }

        $title = \ltrim($value, '#');
        $title = \trim($title);

        return $title;
    }

    /**
     * @param  list<string>                                                          $ingredients
     * @return list<array{name: string, is_header: bool, header_title: null|string}>
     */
    public static function mapIngredientsWithHeaders(array $ingredients): array
    {
        return \array_map(static function (string $ingredient): array {
            $isHeader = self::isHeaderIngredient($ingredient);

            return [
                'name' => $ingredient,
                'is_header' => $isHeader,
                'header_title' => $isHeader ? self::normalizeHeaderTitle($ingredient) : null,
            ];
        }, $ingredients);
    }

    /**
     * @param  array<int, string> $items
     * @return list<string>
     */
    public static function sanitizeCollection(array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            $value = \trim($item);

            if ($value === '') {
                continue;
            }

            $out[] = $value;
        }

        return \array_values(\array_unique($out));
    }

    /**
     * @return list<string>
     */
    public static function tagsFromCsv(string $tagsCsv): array
    {
        /** @var array<int, string> $parts */
        $parts = \explode(',', $tagsCsv);

        return self::sanitizeCollection($parts);
    }

    /**
     * @param list<int> $categoryIds
     */
    public static function userOwnsAllCategories(int $userId, array $categoryIds): bool
    {
        if ($categoryIds === []) {
            return true;
        }

        $uniqueIds = \array_values(\array_unique($categoryIds));

        return Category::query()
            ->where('user_id', $userId)
            ->whereIn('id', $uniqueIds)
            ->count() === \count($uniqueIds);
    }

    public static function summarizeRecipe(Recipe $recipe): array
    {
        /** @var list<string> $ingredientNames */
        $ingredientNames = $recipe->ingredients->pluck('name')->values()->all();
        $ingredientItems = self::mapIngredientsWithHeaders($ingredientNames);
        $headerCount = \count(\array_filter($ingredientItems, static fn(array $item): bool => $item['is_header'] === true));

        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'description' => $recipe->description,
            'note' => $recipe->note,
            'public' => (bool) $recipe->public,
            'favourite' => (bool) $recipe->favourite,
            'category_names' => $recipe->categories->pluck('name')->values()->all(),
            'tags' => $recipe->tags->pluck('name')->values()->all(),
            'ingredient_count' => \count($ingredientNames),
            'ingredient_header_count' => $headerCount,
            'updated_at' => $recipe->updated_at?->toIso8601String(),
        ];
    }

    public static function detailRecipe(Recipe $recipe): array
    {
        /** @var list<string> $ingredientNames */
        $ingredientNames = $recipe->ingredients->pluck('name')->values()->all();

        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'description' => $recipe->description,
            'note' => $recipe->note,
            'public' => (bool) $recipe->public,
            'favourite' => (bool) $recipe->favourite,
            'categories' => $recipe->categories->map(static fn(Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values()->all(),
            'ingredients' => $ingredientNames,
            'ingredients_structured' => self::mapIngredientsWithHeaders($ingredientNames),
            'tags' => $recipe->tags->pluck('name')->values()->all(),
            'created_at' => $recipe->created_at?->toIso8601String(),
            'updated_at' => $recipe->updated_at?->toIso8601String(),
        ];
    }
}
