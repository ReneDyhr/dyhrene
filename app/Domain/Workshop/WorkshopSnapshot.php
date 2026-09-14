<?php

declare(strict_types=1);

namespace App\Domain\Workshop;

final readonly class WorkshopSnapshot
{
    public function __construct(
        public int $draftCount,
        public int $lockedCount,
    ) {}
}
