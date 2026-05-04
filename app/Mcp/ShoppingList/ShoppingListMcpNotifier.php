<?php

declare(strict_types=1);

namespace App\Mcp\ShoppingList;

use App\Events\ShoppingList as ShoppingListEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class ShoppingListMcpNotifier
{
    public static function notifyUpdated(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            \broadcast(new ShoppingListEvent($user, 'update', []));
        }
    }
}
