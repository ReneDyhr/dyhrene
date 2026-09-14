<?php

declare(strict_types=1);

namespace App\Domain\Overview;

final readonly class LatestReceipt
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $vendor,
        public string $currency,
        public \DateTimeImmutable $date,
        public float $amount,
    ) {}
}
