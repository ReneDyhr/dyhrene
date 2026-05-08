<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Models\Recipe;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name(value: 'recipe_create')]
#[Description(value: 'Create a new recipe for the authenticated user including categories, ingredients, and tags. Ingredient values starting with "#" are treated as section headers.')]
class CreateRecipeTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{name: string, description: string, note?: string, public?: bool, category_ids: list<int>, ingredients: list<string>, tags?: list<string>} $validated */
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'note' => 'sometimes|string',
            'public' => 'sometimes|boolean',
            'category_ids' => 'required|array|min:1|max:50',
            'category_ids.*' => 'integer|min:1',
            'ingredients' => 'required|array|min:1|max:200',
            'ingredients.*' => 'string|min:1|max:255',
            'tags' => 'sometimes|array|max:100',
            'tags.*' => 'string|min:1|max:100',
        ]);

        $categoryIds = \array_values(\array_unique($validated['category_ids']));

        if (!RecipeToolSupport::userOwnsAllCategories($userId, $categoryIds)) {
            return Response::error('One or more category_ids do not belong to the authenticated user.');
        }

        $ingredients = RecipeToolSupport::sanitizeCollection($validated['ingredients']);
        $tags = RecipeToolSupport::sanitizeCollection($validated['tags'] ?? []);

        if ($ingredients === []) {
            return Response::error('At least one non-empty ingredient is required.');
        }

        /** @var Recipe $recipe */
        $recipe = DB::transaction(function () use ($validated, $userId, $categoryIds, $ingredients, $tags): Recipe {
            $recipe = Recipe::query()->create([
                'user_id' => $userId,
                'name' => $validated['name'],
                'description' => $validated['description'],
                'note' => $validated['note'] ?? '',
                'public' => $validated['public'] ?? false,
            ]);

            $recipe->categories()->sync($categoryIds);

            foreach ($ingredients as $ingredient) {
                $recipe->ingredients()->create(['name' => $ingredient]);
            }

            foreach ($tags as $tag) {
                $recipe->tags()->create(['name' => $tag]);
            }

            return $recipe;
        });

        $recipe->load(['categories:id,name,slug', 'ingredients:id,recipe_id,name', 'tags:id,recipe_id,name']);

        return Response::structured([
            'created' => true,
            'recipe' => RecipeToolSupport::detailRecipe($recipe),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('Recipe name.'),
            'description' => $schema->string()->required()->description('Recipe description or method.'),
            'note' => $schema->string()->description('Optional notes.'),
            'public' => $schema->boolean()->description('Whether the recipe is public. Defaults to false.'),
            'category_ids' => $schema->array()->items($schema->integer())->required()->description('Category IDs to assign.'),
            'ingredients' => $schema->array()->items($schema->string())->required()->description('Ingredient names in order. Prefix with "#" to create a header row (example: "#Sauce").'),
            'tags' => $schema->array()->items($schema->string())->description('Optional tag names.'),
        ];
    }
}
