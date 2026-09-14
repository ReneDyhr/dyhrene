<?php

declare(strict_types=1);

namespace App\Domain\Household\Contracts;

use App\Domain\Household\HouseholdSnapshot;

interface HouseholdSnapshotReaderInterface
{
    public function read(int $userId, \DateTimeImmutable $now): HouseholdSnapshot;
}
