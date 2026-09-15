<?php

declare(strict_types=1);

namespace App\Services\Workshop;

use App\Domain\Workshop\Contracts\WorkshopSnapshotReaderInterface;
use App\Domain\Workshop\WorkshopSnapshot;
use App\Models\PrintJob;

final class EloquentWorkshopSnapshotReader implements WorkshopSnapshotReaderInterface
{
    public function read(): WorkshopSnapshot
    {
        return new WorkshopSnapshot(
            draftCount: PrintJob::query()->draft()->count(),
            lockedCount: PrintJob::query()->locked()->count(),
        );
    }
}
