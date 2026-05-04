<?php

declare(strict_types=1);

namespace App\Mcp\Tools\ShoppingList;

use App\Mcp\ShoppingList\ShoppingListMcpNotifier;
use App\Models\ShoppingList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name(value: 'shopping_list_remove_item')]
#[Description(value: 'Remove a shopping list item by id (only if it belongs to the user).')]
#[IsDestructive(value: true)]
class RemoveShoppingListItemTool extends Tool
{
    public function handle(Request $request): Response
    {
        /** @var array{id: int} $validated */
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $item = ShoppingList::forAuthUser()->whereKey($validated['id'])->first();

        if ($item === null) {
            return Response::json(['deleted' => false, 'reason' => 'not_found']);
        }

        $item->delete();
        ShoppingListMcpNotifier::notifyUpdated();

        return Response::json(['deleted' => true, 'id' => $validated['id']]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Shopping list row id.')->required(),
        ];
    }
}
