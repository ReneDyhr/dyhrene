<?php

declare(strict_types=1);

use App\Domain\Nature\NatureSnapshot;
use App\Enums\SpeciesStatusEnum;
use App\Models\Observation;
use App\Models\Species;
use App\Models\User;
use App\Services\Nature\EloquentNatureSnapshotReader;

\covers(EloquentNatureSnapshotReader::class);
\covers(NatureSnapshot::class);

\it('counts observations and distinct accepted species for the user', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $speciesA = Species::factory()->for($owner)->create();
    $speciesB = Species::factory()->for($owner)->create();
    $rejectedSpecies = Species::factory()->for($owner)->create(['status' => SpeciesStatusEnum::Rejected]);
    $otherSpecies = Species::factory()->for($other)->create();

    Observation::factory()->for($speciesA)->for($owner)->create();
    Observation::factory()->for($speciesA)->for($owner)->create();
    Observation::factory()->for($speciesB)->for($owner)->create();
    Observation::factory()->for($rejectedSpecies)->for($owner)->create();
    Observation::factory()->for($otherSpecies)->for($owner)->create();

    $snapshot = (new EloquentNatureSnapshotReader())->read($owner->id);

    \expect($snapshot->observationCount)->toBe(3)
        ->and($snapshot->speciesCount)->toBe(2);
});

\it('returns an empty nature snapshot for a user without data', function (): void {
    $user = User::factory()->create();

    $snapshot = (new EloquentNatureSnapshotReader())->read($user->id);

    \expect($snapshot->observationCount)->toBe(0)
        ->and($snapshot->speciesCount)->toBe(0);
});
