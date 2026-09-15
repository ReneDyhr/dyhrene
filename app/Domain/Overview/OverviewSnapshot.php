<?php

declare(strict_types=1);

namespace App\Domain\Overview;

final readonly class OverviewSnapshot
{
    public function __construct(
        public RecipeOverview $recipes,
        public ShoppingListOverview $shoppingList,
        public ReceiptOverview $receipts,
        public InventoryOverview $inventory,
        public ObservationOverview $observations,
        public WildEdibleOverview $wildEdibles,
    ) {}
}
