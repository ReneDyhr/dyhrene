<?php

declare(strict_types=1);

namespace App\Domain\Overview;

final readonly class InventoryOverview
{
    public function __construct(
        public int $count,
    ) {}
}
