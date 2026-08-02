<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;

class DeleteInventoryItemAction
{
    public function handle(InventoryItem $item): void
    {
        $item->delete();
    }
}
