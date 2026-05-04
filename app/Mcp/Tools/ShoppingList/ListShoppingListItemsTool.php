<?php

declare(strict_types=1);

namespace App\Mcp\Tools\ShoppingList;

use App\Models\ShoppingList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'shopping_list_list')]
#[Description(value: 'List shopping list items for the authenticated user (active and checked groups, with ids).')]
#[IsReadOnly(value: true)]
class ListShoppingListItemsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $active = ShoppingList::forAuthUser()
            ->where('status', '=', 'active')
            ->orderBy('order', 'ASC')
            ->get(['id', 'name', 'order', 'status']);

        $checked = ShoppingList::forAuthUser()
            ->where('status', '=', 'checked')
            ->orderBy('order', 'ASC')
            ->get(['id', 'name', 'order', 'status']);

        return Response::json([
            'active' => $active->map(fn(ShoppingList $row): array => $this->rowToArray($row))->values()->all(),
            'checked' => $checked->map(fn(ShoppingList $row): array => $this->rowToArray($row))->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array{id: int, name: string, order: int, status: string}
     */
    private function rowToArray(ShoppingList $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'order' => $row->order,
            'status' => $row->status,
        ];
    }
}
