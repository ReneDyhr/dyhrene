<?php

declare(strict_types=1);

use App\Domain\Workshop\WorkshopSnapshot;
use App\Models\PrintJob;
use App\Services\Workshop\EloquentWorkshopSnapshotReader;

\covers(EloquentWorkshopSnapshotReader::class);
\covers(WorkshopSnapshot::class);

\it('counts draft and locked print jobs', function (): void {
    PrintJob::factory()->count(2)->draft()->create();
    PrintJob::factory()->count(3)->locked()->create();

    $snapshot = (new EloquentWorkshopSnapshotReader())->read();

    \expect($snapshot->draftCount)->toBe(2)
        ->and($snapshot->lockedCount)->toBe(3);
});

\it('returns zero counts when no print jobs exist', function (): void {
    $snapshot = (new EloquentWorkshopSnapshotReader())->read();

    \expect($snapshot->draftCount)->toBe(0)
        ->and($snapshot->lockedCount)->toBe(0);
});
