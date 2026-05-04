<?php

declare(strict_types=1);

namespace App\Mcp\ShoppingList;

final class ShoppingListMcpRoute
{
    public const PATH = 'mcp/shopping-list';

    public static function endpointUrl(): string
    {
        return \url('/' . self::PATH);
    }
}
