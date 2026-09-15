<?php

declare(strict_types=1);

namespace App\Domain\Overview;

final readonly class ShoppingListOverview
{
    public function __construct(
        public int $activeActionableCount,
    ) {}
}
