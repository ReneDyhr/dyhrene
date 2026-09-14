<?php

declare(strict_types=1);

namespace App\Domain\Kitchen;

final readonly class RecipeSummary
{
    /**
     * @param list<string> $categoryNames
     * @param list<string> $tagNames
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $categoryNames,
        public int $ingredientCount,
        public array $tagNames,
        public \DateTimeImmutable $createdAt,
    ) {}
}
