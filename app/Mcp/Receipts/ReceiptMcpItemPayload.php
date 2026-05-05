<?php

declare(strict_types=1);

namespace App\Mcp\Receipts;

use App\Models\ReceiptItem;

final class ReceiptMcpItemPayload
{
    /**
     * @return array{id: int, name: string, quantity: int, amount: mixed, category_id: int, category_name: null|string}
     */
    public static function row(ReceiptItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'quantity' => $item->quantity,
            'amount' => $item->amount,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name,
        ];
    }
}
