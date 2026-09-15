<?php

declare(strict_types=1);

namespace App\Domain\Overview;

final readonly class ReceiptOverview
{
    /**
     * @param array<string, float> $amountsByCurrency
     */
    public function __construct(
        public int $currentMonthCount,
        public ?LatestReceipt $latest,
        public array $amountsByCurrency,
    ) {}
}
