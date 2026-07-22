<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadBirdnetDetectionRequest;
use App\Models\BirdnetDetection;
use App\Models\Observation;
use App\Models\Species;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class BirdnetDetectionController
{
    public function __invoke(UploadBirdnetDetectionRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $metadata */
        $metadata = \json_decode($request->string('metadata')->value(), true, 512, \JSON_THROW_ON_ERROR);

        $detectionUuid = \strval($metadata['id'] ?? '');

        /** @var \App\Models\User $user */
        $user = \auth()->user();

        // Idempotent: return 200 if already exists (scoped to user)
        $existing = BirdnetDetection::query()
            ->where('detection_uuid', $detectionUuid)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            return \response()->json([
                'message' => 'Detection already exists',
                'detection' => $existing,
            ], 200);
        }

        // Store audio file to Wasabi if present (before transaction)
        $audioPath = null;

        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            \assert($file instanceof \Illuminate\Http\UploadedFile);
            $audioPath = Storage::disk('wasabi')->put('birdnet-audio', $file);
        }

        // Extract typed values from metadata before entering closure
        $scientificName = \strval($metadata['scientific_name'] ?? '');
        $commonName = \strval($metadata['common_name'] ?? '');
        $confidence = \floatval($metadata['confidence'] ?? 0.0);
        $startTime = \floatval($metadata['start_time'] ?? 0.0);
        $endTime = \floatval($metadata['end_time'] ?? 0.0);
        $recordedAtStr = \strval($metadata['recorded_at'] ?? '');
        $latitude = \floatval($metadata['latitude'] ?? 0.0);
        $longitude = \floatval($metadata['longitude'] ?? 0.0);
        $segmentId = $metadata['segment_id'] ?? null;

        // Wrap all DB writes in a transaction
        [$detection, $observation] = DB::transaction(function () use (
            $user,
            $scientificName,
            $commonName,
            $confidence,
            $startTime,
            $endTime,
            $recordedAtStr,
            $latitude,
            $longitude,
            $segmentId,
            $detectionUuid,
            $metadata,
            $audioPath,
        ): array {
            // Find or create Species scoped to user (firstOrCreate with try/catch)
            $species = $this->findOrCreateSpecies($scientificName, $commonName, $user->id);

            // Create BirdnetDetection
            $detection = BirdnetDetection::query()->create([
                'detection_uuid' => $detectionUuid,
                'scientific_name' => $scientificName,
                'common_name' => $commonName,
                'confidence' => $confidence,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'recorded_at' => $recordedAtStr,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'audio_path' => $audioPath,
                'segment_id' => $segmentId,
                'raw_metadata' => $metadata,
                'species_id' => $species->id,
                'user_id' => $user->id,
            ]);

            // Parse recorded_at and split into date + time for Observation
            $dt = $recordedAtStr !== ''
                ? new \DateTimeImmutable($recordedAtStr)
                : new \DateTimeImmutable();

            // Create Observation
            $observation = Observation::query()->create([
                'species_id' => $species->id,
                'user_id' => $user->id,
                'observed_at' => $dt->format('Y-m-d'),
                'observed_time' => $dt->format('H:i:s'),
                'location' => \sprintf('%s, %s', $latitude, $longitude),
                'source' => 'birdnet',
            ]);

            // Link observation to detection
            $detection->update(['observation_id' => $observation->id]);

            return [$detection, $observation];
        });

        $detection->load(['species', 'observation']);

        return \response()->json([
            'message' => 'Detection uploaded successfully',
            'detection' => $detection,
        ], 201);
    }

    /**
     * Find or create a Species record scoped to the user.
     * Uses firstOrCreate with try/catch to handle race conditions
     * on the unique composite index [scientific_name, user_id].
     */
    private function findOrCreateSpecies(string $scientificName, string $commonName, int $userId): Species
    {
        try {
            /** @var Species $species */
            $species = Species::query()->firstOrCreate(
                [
                    'scientific_name' => $scientificName,
                    'user_id' => $userId,
                ],
                [
                    'common_name' => $commonName,
                ],
            );

            return $species;
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Race condition: another request created it between our check and insert.
            // Fetch the now-existing record.
            /** @var Species $species */
            $species = Species::query()
                ->where('scientific_name', $scientificName)
                ->where('user_id', $userId)
                ->firstOrFail();

            return $species;
        }
    }
}
