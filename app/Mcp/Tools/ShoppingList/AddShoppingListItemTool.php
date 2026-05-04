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

#[Name(value: 'shopping_list_add_item')]
#[Description(value: 'Add a shopping list item (name must be at least 3 characters).')]
class AddShoppingListItemTool extends Tool
{
    public function handle(Request $request): Response
    {
        /** @var array{name: string} $validated */
        $validated = $request->validate([
            'name' => 'required|string|min:3',
        ]);

        $lastOrder = ShoppingList::forAuthUser()->orderBy('order', 'DESC')->first();

        if ($lastOrder === null) {
            $lastOrder = new ShoppingList();
            $lastOrder->order = 0;
        }

        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        $item = new ShoppingList();
        $item->name = $validated['name'];
        $item->user_id = $userId;
        $item->order = $lastOrder->order + 1;
        $item->status = 'active';
        $item->save();

        ShoppingListMcpNotifier::notifyUpdated();

        return Response::json([
            'id' => $item->id,
            'name' => $item->name,
            'order' => $item->order,
            'status' => $item->status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Item label (minimum 3 characters).')->required(),
        ];
    }
}
