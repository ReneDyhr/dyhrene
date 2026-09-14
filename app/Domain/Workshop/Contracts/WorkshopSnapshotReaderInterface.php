<?php

declare(strict_types=1);

namespace App\Domain\Workshop\Contracts;

use App\Domain\Workshop\WorkshopSnapshot;

interface WorkshopSnapshotReaderInterface
{
    public function read(): WorkshopSnapshot;
}
