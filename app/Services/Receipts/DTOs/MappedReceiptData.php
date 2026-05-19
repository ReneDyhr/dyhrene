<?php

declare(strict_types=1);

namespace App\Services\Receipts\DTOs;

final readonly class MappedReceiptData
{
    /**
     * @param array{name: string, vendor: ?string, description: ?string, currency: string, date: string, file_path?: ?string} $header
     * @param list<array{name: string, quantity: int, amount: float, category_id: int}>                                       $items
     */
    public function __construct(
        public array $header,
        public array $items,
    ) {}
}
