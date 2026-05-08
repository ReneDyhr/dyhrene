<?php

declare(strict_types=1);

use App\Mcp\Servers\RecipeServer;
use App\Models\Category;
use App\Models\Icon;
use App\Models\Recipe;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

\uses()->group('feature');

/**
 * @return non-empty-string
 */
function recipeMcpSessionId(TestCase $test, User $user): string
{
    Passport::actingAs($user, ['mcp:use']);

    $init = $test->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test',
                'version' => '0.0.1',
            ],
        ],
    ]);

    $init->assertStatus(200);
    $sessionId = $init->headers->get('MCP-Session-Id');
    \expect($sessionId)->not->toBeEmpty();

    return (string) $sessionId;
}

function createRecipeCategory(User $user, string $name, string $slug): Category
{
    $icon = Icon::query()->create([
        'name' => $name . ' icon',
        'class' => 'fa-solid fa-utensils',
    ]);

    return Category::query()->create([
        'user_id' => $user->id,
        'icon_id' => $icon->id,
        'name' => $name,
        'slug' => $slug,
    ]);
}

\test('mcp recipes returns 401 without authentication', function (): void {
    $response = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test',
                'version' => '0.0.1',
            ],
        ],
    ]);

    $response->assertStatus(401);
})->covers(RecipeServer::class);

\test('mcp recipes initialize succeeds with mcp use scope', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['mcp:use']);

    $init = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test',
                'version' => '0.0.1',
            ],
        ],
    ]);

    $init->assertStatus(200);
    $init->assertHeader('MCP-Session-Id');
    $init->assertJsonPath('result.serverInfo.name', 'Recipes Server');
})->covers(RecipeServer::class);

\test('mcp recipe_create persists recipe relations and recipe_get returns details', function (): void {
    $user = User::factory()->create();
    $catA = \createRecipeCategory($user, 'Dinner', 'dinner');
    $catB = \createRecipeCategory($user, 'Quick', 'quick');
    $sessionId = \recipeMcpSessionId($this, $user);

    $create = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'recipe_create',
            'arguments' => [
                'name' => 'Tomato Pasta',
                'description' => 'Cook and combine.',
                'note' => 'Family favorite',
                'public' => true,
                'category_ids' => [$catA->id, $catB->id],
                'ingredients' => ['#Sauce', 'Tomato', 'Basil'],
                'tags' => ['Italian', 'Weeknight'],
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $create->assertStatus(200);
    $create->assertJsonPath('result.isError', false);
    $recipeId = (int) $create->json('result.structuredContent.recipe.id');
    \expect($recipeId)->toBeGreaterThan(0);

    $get = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'recipe_get',
            'arguments' => ['recipe_id' => $recipeId],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $get->assertStatus(200);
    $get->assertJsonPath('result.structuredContent.recipe.name', 'Tomato Pasta');
    $get->assertJsonPath('result.structuredContent.recipe.ingredients.0', '#Sauce');
    $get->assertJsonPath('result.structuredContent.recipe.ingredients_structured.0.is_header', true);
    $get->assertJsonPath('result.structuredContent.recipe.ingredients_structured.0.header_title', 'Sauce');
    $get->assertJsonPath('result.structuredContent.recipe.ingredients_structured.1.is_header', false);
    $get->assertJsonPath('result.structuredContent.recipe.tags.1', 'Weeknight');
})->covers(RecipeServer::class);

\test('mcp recipe_update replaces categories ingredients and tags', function (): void {
    $user = User::factory()->create();
    $oldCategory = \createRecipeCategory($user, 'Old', 'old');
    $newCategory = \createRecipeCategory($user, 'New', 'new');

    $recipe = Recipe::factory()->create([
        'user_id' => $user->id,
        'name' => 'Before',
        'description' => 'Before description',
        'note' => 'Before note',
        'public' => false,
    ]);
    $recipe->categories()->attach($oldCategory->id);
    $recipe->ingredients()->create(['name' => 'Salt']);
    $recipe->tags()->create(['name' => 'OldTag']);

    $sessionId = \recipeMcpSessionId($this, $user);

    $update = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'recipe_update',
            'arguments' => [
                'recipe_id' => $recipe->id,
                'name' => 'After',
                'description' => 'After description',
                'note' => 'After note',
                'public' => true,
                'category_ids' => [$newCategory->id],
                'ingredients' => ['#Topping', 'Pepper', 'Olive Oil'],
                'tags' => ['Fresh'],
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $update->assertStatus(200);
    $update->assertJsonPath('result.structuredContent.recipe.name', 'After');
    $update->assertJsonPath('result.structuredContent.recipe.ingredients.0', '#Topping');
    $update->assertJsonPath('result.structuredContent.recipe.ingredients_structured.0.is_header', true);
    $update->assertJsonPath('result.structuredContent.recipe.ingredients_structured.0.header_title', 'Topping');
    $update->assertJsonPath('result.structuredContent.recipe.tags.0', 'Fresh');

    $recipe->refresh()->load(['categories', 'ingredients', 'tags']);
    \expect($recipe->categories->pluck('id')->all())->toBe([$newCategory->id]);
    \expect($recipe->ingredients->pluck('name')->all())->toBe(['#Topping', 'Pepper', 'Olive Oil']);
    \expect($recipe->tags->pluck('name')->all())->toBe(['Fresh']);
})->covers(RecipeServer::class);

\test('mcp recipe_delete soft deletes recipe', function (): void {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);
    $sessionId = \recipeMcpSessionId($this, $user);

    $delete = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'recipe_delete',
            'arguments' => [
                'recipe_id' => $recipe->id,
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $delete->assertStatus(200);
    $delete->assertJsonPath('result.structuredContent.deleted', true);
    \expect(Recipe::withTrashed()->find($recipe->id)?->trashed())->toBeTrue();
})->covers(RecipeServer::class);

\test('mcp recipe_list filters by category and tags', function (): void {
    $user = User::factory()->create();
    $catDinner = \createRecipeCategory($user, 'Dinner', 'dinner');
    $catLunch = \createRecipeCategory($user, 'Lunch', 'lunch');

    $recipeOne = Recipe::factory()->create(['user_id' => $user->id, 'name' => 'Ramen Bowl']);
    $recipeOne->categories()->attach($catDinner->id);
    $recipeOne->ingredients()->create(['name' => 'Noodles']);
    $recipeOne->tags()->create(['name' => 'asian']);

    $recipeTwo = Recipe::factory()->create(['user_id' => $user->id, 'name' => 'Chicken Wrap']);
    $recipeTwo->categories()->attach($catLunch->id);
    $recipeTwo->ingredients()->create(['name' => 'Chicken']);
    $recipeTwo->tags()->create(['name' => 'quick']);

    $sessionId = \recipeMcpSessionId($this, $user);

    $list = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'recipe_list',
            'arguments' => [
                'category_ids' => [$catDinner->id],
                'tags' => ['asian'],
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $list->assertStatus(200);
    $list->assertJsonPath('result.isError', false);
    $list->assertJsonPath('result.structuredContent.recipes.0.id', $recipeOne->id);
    $ids = \collect($list->json('result.structuredContent.recipes'))->pluck('id')->all();
    \expect($ids)->not->toContain($recipeTwo->id);
})->covers(RecipeServer::class);

\test('mcp recipe_search ranks stronger matches and searches non-name fields', function (): void {
    $user = User::factory()->create();
    $category = \createRecipeCategory($user, 'Comfort', 'comfort');

    $strong = Recipe::factory()->create([
        'user_id' => $user->id,
        'name' => 'Tomato Soup',
        'description' => 'Creamy and warm',
    ]);
    $strong->categories()->attach($category->id);
    $strong->ingredients()->create(['name' => 'Tomato']);
    $strong->tags()->create(['name' => 'winter']);

    $ingredientOnly = Recipe::factory()->create([
        'user_id' => $user->id,
        'name' => 'Garden Blend',
        'description' => 'Mixed vegetables',
    ]);
    $ingredientOnly->categories()->attach($category->id);
    $ingredientOnly->ingredients()->create(['name' => 'Tomato paste']);
    $ingredientOnly->tags()->create(['name' => 'quick']);

    $sessionId = \recipeMcpSessionId($this, $user);

    $search = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'recipe_search',
            'arguments' => [
                'query' => 'tomato soup',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $search->assertStatus(200);
    $search->assertJsonPath('result.isError', false);
    $search->assertJsonPath('result.structuredContent.recipes.0.id', $strong->id);
    $search->assertJsonPath('result.structuredContent.recipes.0.score', fn(mixed $score): bool => \is_int($score) && $score > 0);

    $searchIngredient = $this->postJson('/mcp/recipes', [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'recipe_search',
            'arguments' => [
                'query' => 'paste',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $searchIngredient->assertStatus(200);
    $searchIngredient->assertJsonPath('result.structuredContent.recipes.0.id', $ingredientOnly->id);
})->covers(RecipeServer::class);
