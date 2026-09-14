<?php

declare(strict_types=1);

namespace App\Services\Nature;

use App\Domain\Nature\Contracts\NatureSnapshotReaderInterface;
use App\Domain\Nature\NatureSnapshot;
use App\Models\Observation;
use App\Models\Species;
use Illuminate\Database\Eloquent\Builder;

final class EloquentNatureSnapshotReader implements NatureSnapshotReaderInterface
{
    public function read(int $userId): NatureSnapshot
    {
        $base = Observation::query()
            ->where('user_id', $userId)
            ->whereHas('species', static function (Builder $query) use ($userId): void {
                /** @var Builder<Species> $query */
                $query->where('user_id', $userId)->where('status', '!=', 'rejected');
            });

        return new NatureSnapshot(
            observationCount: (clone $base)->count(),
            speciesCount: (clone $base)->distinct()->count('species_id'),
        );
    }
}
