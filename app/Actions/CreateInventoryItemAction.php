<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\User;

class CreateInventoryItemAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(User $user, array $data): InventoryItem
    {
        $data['user_id'] = $user->id;

        return InventoryItem::query()->create($data);
    }
}
