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

#[Name(value: 'recipe_update')]
#[Description(value: 'Update one recipe for the authenticated user. Categories, ingredients, and tags replace existing values when provided. Ingredient values starting with "#" are treated as section headers.')]
class UpdateRecipeTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{
         *   recipe_id: int,
         *   name?: string,
         *   description?: string,
         *   note?: string,
         *   public?: bool,
         *   category_ids?: list<int>,
         *   ingredients?: list<string>,
         *   tags?: list<string>
         * } $validated */
        $validated = $request->validate([
            'recipe_id' => 'required|integer|min:1',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'note' => 'sometimes|string',
            'public' => 'sometimes|boolean',
            'category_ids' => 'sometimes|array|min:1|max:50',
            'category_ids.*' => 'integer|min:1',
            'ingredients' => 'sometimes|array|min:1|max:200',
            'ingredients.*' => 'string|min:1|max:255',
            'tags' => 'sometimes|array|max:100',
            'tags.*' => 'string|min:1|max:100',
        ]);

        $recipeId = $validated['recipe_id'];

        $recipe = Recipe::forAuthUser()
            ->with(['categories:id,name,slug', 'ingredients:id,recipe_id,name', 'tags:id,recipe_id,name'])
            ->whereKey($recipeId)
            ->first();

        if ($recipe === null) {
            return Response::error('Recipe not found.');
        }

        if (\array_key_exists('category_ids', $validated)) {
            $categoryIds = \array_values(\array_unique($validated['category_ids']));

            if (!RecipeToolSupport::userOwnsAllCategories($userId, $categoryIds)) {
                return Response::error('One or more category_ids do not belong to the authenticated user.');
            }

            $validated['category_ids'] = $categoryIds;
        }

        if (\array_key_exists('ingredients', $validated)) {
            $ingredients = RecipeToolSupport::sanitizeCollection($validated['ingredients']);

            if ($ingredients === []) {
                return Response::error('At least one non-empty ingredient is required when updating ingredients.');
            }

            $validated['ingredients'] = $ingredients;
        }

        if (\array_key_exists('tags', $validated)) {
            $validated['tags'] = RecipeToolSupport::sanitizeCollection($validated['tags']);
        }

        DB::transaction(function () use ($recipe, $validated): void {
            $recipeData = [];

            foreach (['name', 'description', 'note', 'public'] as $field) {
                if (\array_key_exists($field, $validated)) {
                    $recipeData[$field] = $validated[$field];
                }
            }

            if ($recipeData !== []) {
                $recipe->update($recipeData);
            }

            if (\array_key_exists('category_ids', $validated)) {
                /** @var list<int> $categoryIds */
                $categoryIds = $validated['category_ids'];
                $recipe->categories()->sync($categoryIds);
            }

            if (\array_key_exists('ingredients', $validated)) {
                /** @var list<string> $ingredients */
                $ingredients = $validated['ingredients'];
                $recipe->ingredients()->delete();

                foreach ($ingredients as $ingredient) {
                    $recipe->ingredients()->create(['name' => $ingredient]);
                }
            }

            if (\array_key_exists('tags', $validated)) {
                /** @var list<string> $tags */
                $tags = $validated['tags'];
                $recipe->tags()->delete();

                foreach ($tags as $tag) {
                    $recipe->tags()->create(['name' => $tag]);
                }
            }
        });

        $recipe->refresh()->load(['categories:id,name,slug', 'ingredients:id,recipe_id,name', 'tags:id,recipe_id,name']);

        return Response::structured([
            'updated' => true,
            'recipe' => RecipeToolSupport::detailRecipe($recipe),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'recipe_id' => $schema->integer()->required()->description('Recipe ID to update.'),
            'name' => $schema->string()->description('Optional new recipe name.'),
            'description' => $schema->string()->description('Optional new description.'),
            'note' => $schema->string()->description('Optional note.'),
            'public' => $schema->boolean()->description('Optional public flag.'),
            'category_ids' => $schema->array()->items($schema->integer())->description('Optional full replacement set of category IDs.'),
            'ingredients' => $schema->array()->items($schema->string())->description('Optional full replacement ingredient list. Prefix with "#" to mark section headers (example: "#Dough").'),
            'tags' => $schema->array()->items($schema->string())->description('Optional full replacement tag list.'),
        ];
    }
}
