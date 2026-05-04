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

#[Name(value: 'shopping_list_check_item')]
#[Description(value: 'Mark a shopping list item as checked (completed). Names starting with `#` are section headers and cannot be checked.')]
class CheckShoppingListItemTool extends Tool
{
    public function handle(Request $request): Response
    {
        /** @var array{id: int} $validated */
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $item = ShoppingList::forAuthUser()->whereKey($validated['id'])->first();

        if ($item === null) {
            return Response::json(['updated' => false, 'reason' => 'not_found']);
        }

        if ($item->isSectionHeader()) {
            return Response::json(['updated' => false, 'reason' => 'section_header']);
        }

        $item->status = 'checked';
        $item->save();

        ShoppingListMcpNotifier::notifyUpdated();

        return Response::json(['updated' => true, 'id' => $item->id, 'status' => $item->status]);
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
