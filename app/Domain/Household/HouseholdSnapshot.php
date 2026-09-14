<?php

declare(strict_types=1);

namespace App\Domain\Household;

use App\Domain\Overview\InventoryOverview;
use App\Domain\Overview\ReceiptOverview;

final readonly class HouseholdSnapshot
{
    /**
     * @param list<ReceiptSummary> $latestReceipts
     */
    public function __construct(
        public ReceiptOverview $receipts,
        public InventoryOverview $inventory,
        public array $latestReceipts,
    ) {}
}
