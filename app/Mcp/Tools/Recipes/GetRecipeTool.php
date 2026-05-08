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

#[Name(value: 'recipe_get')]
#[Description(value: 'Get one recipe with ingredients, categories, and tags for the authenticated user.')]
#[IsReadOnly(value: true)]
class GetRecipeTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{recipe_id: int} $validated */
        $validated = $request->validate([
            'recipe_id' => 'required|integer|min:1',
        ]);

        $recipe = Recipe::forAuthUser()
            ->with(['categories:id,name,slug', 'ingredients:id,recipe_id,name', 'tags:id,recipe_id,name'])
            ->whereKey($validated['recipe_id'])
            ->first();

        if ($recipe === null) {
            return Response::error('Recipe not found.');
        }

        return Response::structured([
            'recipe' => RecipeToolSupport::detailRecipe($recipe),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'recipe_id' => $schema->integer()->required()->description('Recipe ID to load.'),
        ];
    }
}
