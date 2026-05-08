<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\Recipes\CreateRecipeTool;
use App\Mcp\Tools\Recipes\DeleteRecipeTool;
use App\Mcp\Tools\Recipes\GetRecipeTool;
use App\Mcp\Tools\Recipes\ListRecipeCategoriesTool;
use App\Mcp\Tools\Recipes\ListRecipeTagsTool;
use App\Mcp\Tools\Recipes\ListRecipesTool;
use App\Mcp\Tools\Recipes\SearchRecipesTool;
use App\Mcp\Tools\Recipes\UpdateRecipeTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name(value: 'Recipes Server')]
#[Version(value: '0.1.0')]
#[Instructions(value: <<<'MARKDOWN'
This server manages the signed-in user's recipes.

**Authentication:** OAuth 2.1 via Laravel Passport. Clients must obtain an access token that includes the **`mcp:use`** scope, then send `Authorization: Bearer <token>` on every MCP HTTP request.

**Tools:** Use **`recipe_list_categories`** and **`recipe_list_tags`** to discover available filters. **`recipe_list`** returns recipe summaries and supports filtering by categories and tags. **`recipe_get`** returns one full recipe including ingredients, categories, and tags. Ingredients prefixed with **`#`** are section headers and are also returned in structured form (`is_header`, `header_title`). **`recipe_create`** creates recipe data and linked collections. **`recipe_update`** updates recipe fields and replaces category, ingredient, and tag collections. **`recipe_delete`** soft-deletes a recipe. **`recipe_search`** performs weighted multi-field matching across name, description, note, ingredients, tags, and categories.

**MCP endpoint:** HTTP `POST` to `/mcp/recipes` on this app’s base URL (copy from the web app under **MCP & OAuth (AI assistants)** in the side menu).
MARKDOWN)]
class RecipeServer extends Server
{
    protected array $tools = [
        ListRecipeCategoriesTool::class,
        ListRecipeTagsTool::class,
        ListRecipesTool::class,
        GetRecipeTool::class,
        CreateRecipeTool::class,
        UpdateRecipeTool::class,
        DeleteRecipeTool::class,
        SearchRecipesTool::class,
    ];

    protected array $resources = [
    ];

    protected array $prompts = [
    ];
}
