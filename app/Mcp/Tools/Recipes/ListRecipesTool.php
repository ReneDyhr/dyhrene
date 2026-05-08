<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Models\Recipe;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'recipe_list')]
#[Description(value: 'List recipe summaries for the authenticated user. Supports optional category_ids and tags filters.')]
#[IsReadOnly(value: true)]
class ListRecipesTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{category_ids?: list<int>, tags?: list<string>, limit?: int, offset?: int} $validated */
        $validated = $request->validate([
            'category_ids' => 'sometimes|array|max:50',
            'category_ids.*' => 'integer|min:1',
            'tags' => 'sometimes|array|max:50',
            'tags.*' => 'string|min:1|max:100',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0|max:10000',
        ]);

        $categoryIds = \array_values(\array_unique($validated['category_ids'] ?? []));
        $tags = RecipeToolSupport::sanitizeCollection($validated['tags'] ?? []);
        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        if (!RecipeToolSupport::userOwnsAllCategories($userId, $categoryIds)) {
            return Response::error('One or more category_ids do not belong to the authenticated user.');
        }

        $query = Recipe::forAuthUser()
            ->with(['categories:id,name,slug', 'ingredients:id,recipe_id,name', 'tags:id,recipe_id,name'])
            ->orderByDesc('id');

        if ($categoryIds !== []) {
            $query->whereHas('categories', static function ($categoryQuery) use ($categoryIds): void {
                $categoryQuery->whereIn('categories.id', $categoryIds);
            });
        }

        if ($tags !== []) {
            $query->whereHas('tags', static function ($tagQuery) use ($tags): void {
                foreach ($tags as $index => $tag) {
                    if ($index === 0) {
                        $tagQuery->whereRaw('LOWER(recipe_tags.name) = ?', [\mb_strtolower($tag)]);
                    } else {
                        $tagQuery->orWhereRaw('LOWER(recipe_tags.name) = ?', [\mb_strtolower($tag)]);
                    }
                }
            });
        }

        $total = (clone $query)->count();
        $recipes = $query->offset($offset)->limit($limit)->get();

        return Response::structured([
            'recipes' => $recipes->map(static fn(Recipe $recipe): array => RecipeToolSupport::summarizeRecipe($recipe))->values()->all(),
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category_ids' => $schema->array()->items($schema->integer())->description('Optional category IDs. Returns recipes that belong to any provided category.'),
            'tags' => $schema->array()->items($schema->string())->description('Optional tag names. Returns recipes that contain any provided tag.'),
            'limit' => $schema->integer()->description('Optional page size (default 50, max 100).'),
            'offset' => $schema->integer()->description('Optional page offset (default 0).'),
        ];
    }
}
