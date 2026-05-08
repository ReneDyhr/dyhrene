<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Models\RecipeTag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'recipe_list_tags')]
#[Description(value: 'List distinct tag names used by the authenticated user across all recipes.')]
#[IsReadOnly(value: true)]
class ListRecipeTagsTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        $tags = RecipeTag::query()
            ->join('recipes', 'recipes.id', '=', 'recipe_tags.recipe_id')
            ->where('recipes.user_id', $userId)
            ->whereNull('recipes.deleted_at')
            ->select(DB::raw('LOWER(TRIM(recipe_tags.name)) as normalized_name'))
            ->groupBy('normalized_name')
            ->orderBy('normalized_name')
            ->pluck('normalized_name')
            ->filter(static fn(mixed $tag): bool => \is_string($tag) && $tag !== '')
            ->values()
            ->all();

        return Response::structured([
            'tags' => $tags,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
