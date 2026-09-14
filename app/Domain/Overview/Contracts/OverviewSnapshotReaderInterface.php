<?php

declare(strict_types=1);

namespace App\Domain\Overview\Contracts;

use App\Domain\Overview\OverviewSnapshot;

interface OverviewSnapshotReaderInterface
{
    public function read(int $userId, \DateTimeImmutable $now): OverviewSnapshot;
}
