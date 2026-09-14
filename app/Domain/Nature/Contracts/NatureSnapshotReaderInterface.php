<?php

declare(strict_types=1);

namespace App\Domain\Nature\Contracts;

use App\Domain\Nature\NatureSnapshot;

interface NatureSnapshotReaderInterface
{
    public function read(int $userId): NatureSnapshot;
}
