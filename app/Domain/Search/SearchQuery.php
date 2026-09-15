<?php

declare(strict_types=1);

namespace App\Domain\Search;

final readonly class SearchQuery
{
    public function __construct(
        public string $value,
    ) {}

    public function normalized(): string
    {
        return \trim($this->value);
    }

    public function isEmpty(): bool
    {
        return $this->normalized() === '';
    }
}
