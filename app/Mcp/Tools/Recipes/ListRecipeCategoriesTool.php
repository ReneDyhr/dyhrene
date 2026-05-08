<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'recipe_list_categories')]
#[Description(value: 'List recipe categories for the authenticated user (id, slug, and name).')]
#[IsReadOnly(value: true)]
class ListRecipeCategoriesTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        $rows = Category::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Response::structured([
            'categories' => $rows->map(static fn(Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values()->all(),
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
