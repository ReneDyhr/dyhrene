<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\ShoppingList\AddShoppingListItemTool;
use App\Mcp\Tools\ShoppingList\CheckShoppingListItemTool;
use App\Mcp\Tools\ShoppingList\ListShoppingListItemsTool;
use App\Mcp\Tools\ShoppingList\RemoveShoppingListItemTool;
use App\Mcp\Tools\ShoppingList\ReorderShoppingListItemsTool;
use App\Mcp\Tools\ShoppingList\UncheckShoppingListItemTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name(value: 'Shopping List Server')]
#[Version(value: '0.1.0')]
#[Instructions(value: <<<'MARKDOWN'
This server manages the signed-in user's shopping list.

**Authentication:** OAuth 2.1 via Laravel Passport. Clients must obtain an access token that includes the **`mcp:use`** scope, then send `Authorization: Bearer <token>` on every MCP HTTP request.

**Tools:** Use **`shopping_list_list`** first to read active and checked items (with `id`). **`shopping_list_add_item`** adds a row (name ≥ 3 characters). Prefix a name with **`#`** for a section header (shown bold in the app); section headers cannot be checked or unchecked—only **`shopping_list_remove_item`** applies. **`shopping_list_check_item`** / **`shopping_list_uncheck_item`** toggle status for normal rows. **`shopping_list_remove_item`** deletes any row. **`shopping_list_reorder_items`** sets order for **active** items: pass **`ordered_ids`** as the full top-to-bottom list of active item ids.

**MCP endpoint:** HTTP `POST` to `/mcp/shopping-list` on this app’s base URL (copy from the web app under **MCP & OAuth (AI assistants)** in the side menu).
MARKDOWN)]
class ShoppingListServer extends Server
{
    protected array $tools = [
        ListShoppingListItemsTool::class,
        AddShoppingListItemTool::class,
        RemoveShoppingListItemTool::class,
        CheckShoppingListItemTool::class,
        UncheckShoppingListItemTool::class,
        ReorderShoppingListItemsTool::class,
    ];

    protected array $resources = [
    ];

    protected array $prompts = [
    ];
}
