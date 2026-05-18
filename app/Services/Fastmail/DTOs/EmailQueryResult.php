<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

final readonly class EmailQueryResult
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        public array $ids,
        public int $total,
        public int $position,
        public ?string $queryState,
    ) {}
}
