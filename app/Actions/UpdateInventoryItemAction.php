<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;

class UpdateInventoryItemAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);

        return $item;
    }
}
