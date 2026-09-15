<?php

declare(strict_types=1);

namespace App\Domain\Nature;

final readonly class NatureSnapshot
{
    public function __construct(
        public int $observationCount,
        public int $speciesCount,
    ) {}
}
