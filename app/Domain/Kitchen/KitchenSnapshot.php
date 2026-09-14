<?php

declare(strict_types=1);

namespace App\Domain\Kitchen;

use App\Domain\Overview\RecipeOverview;
use App\Domain\Overview\ShoppingListOverview;

final readonly class KitchenSnapshot
{
    public function __construct(
        public RecipeOverview $recipes,
        public ShoppingListOverview $shoppingList,
    ) {}
}
