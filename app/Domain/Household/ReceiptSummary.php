<?php

declare(strict_types=1);

namespace App\Domain\Household;

final readonly class ReceiptSummary
{
    public function __construct(
        public int $id,
        public string $name,
        public string $category,
        public int $itemCount,
        public string $currency,
        public float $amount,
        public \DateTimeImmutable $date,
    ) {}
}
