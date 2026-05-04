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

#[Name(value: 'shopping_list_reorder_items')]
#[Description(value: 'Set display order for active shopping list items. Pass ordered_ids in top-to-bottom order (all active item ids you want ordered).')]
class ReorderShoppingListItemsTool extends Tool
{
    public function handle(Request $request): Response
    {
        /** @var array{ordered_ids: list<int>} $validated */
        $validated = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer',
        ]);

        $order = 1;

        foreach ($validated['ordered_ids'] as $id) {
            $shoppingListItem = ShoppingList::forAuthUser()->whereKey($id)->first();

            if ($shoppingListItem === null) {
                continue;
            }

            $shoppingListItem->order = $order;
            $shoppingListItem->save();
            $order++;
        }

        ShoppingListMcpNotifier::notifyUpdated();

        return Response::json(['reordered' => true, 'count' => $order - 1]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ordered_ids' => $schema->array()
                ->items($schema->integer())
                ->min(1)
                ->description('Active item ids in desired order (top to bottom).')
                ->required(),
        ];
    }
}
