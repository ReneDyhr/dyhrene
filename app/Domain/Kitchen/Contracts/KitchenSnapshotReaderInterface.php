<?php

declare(strict_types=1);

namespace App\Domain\Kitchen\Contracts;

use App\Domain\Kitchen\KitchenSnapshot;

interface KitchenSnapshotReaderInterface
{
    public function read(int $userId): KitchenSnapshot;
}
