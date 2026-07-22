<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadBirdnetDetectionRequest;
use App\Models\BirdnetDetection;
use App\Models\Observation;
use App\Models\Species;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

final class BirdnetDetectionController
{
    public function __invoke(UploadBirdnetDetectionRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $metadata */
        $metadata = \json_decode($request->string('metadata')->value(), true, 512, \JSON_THROW_ON_ERROR);

        $detectionUuid = (string) $metadata['id'];

        // Idempotent: return 200 if already exists
        $existing = BirdnetDetection::query()
            ->where('detection_uuid', $detectionUuid)
            ->first();

        if ($existing !== null) {
            return \response()->json([
                'message' => 'Detection already exists',
                'detection' => $existing,
            ], 200);
        }

        /** @var \App\Models\User $user */
        $user = \auth()->user();

        // Find or create Species scoped to user
        $scientificName = (string) $metadata['scientific_name'];
        $commonName = (string) ($metadata['common_name'] ?? '');

        $species = Species::query()
            ->where('scientific_name', $scientificName)
            ->where('user_id', $user->id)
            ->first();

        if ($species === null) {
            $species = Species::query()->create([
                'scientific_name' => $scientificName,
                'common_name' => $commonName,
                'user_id' => $user->id,
            ]);
        }

        // Store audio file to Wasabi if present
        $audioPath = null;

        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            \assert($file instanceof \Illuminate\Http\UploadedFile);
            $audioPath = Storage::disk('wasabi')->put('birdnet-audio', $file);
        }

        // Create BirdnetDetection
        $detection = BirdnetDetection::query()->create([
            'detection_uuid' => $detectionUuid,
            'scientific_name' => $scientificName,
            'common_name' => $commonName,
            'confidence' => (float) $metadata['confidence'],
            'start_time' => (float) $metadata['start_time'],
            'end_time' => (float) $metadata['end_time'],
            'recorded_at' => $metadata['recorded_at'],
            'latitude' => (float) $metadata['latitude'],
            'longitude' => (float) $metadata['longitude'],
            'audio_path' => $audioPath,
            'segment_id' => $metadata['segment_id'] ?? null,
            'raw_metadata' => $metadata,
            'species_id' => $species->id,
            'user_id' => $user->id,
        ]);

        // Create Observation
        $observation = Observation::query()->create([
            'species_id' => $species->id,
            'user_id' => $user->id,
            'observed_at' => $metadata['recorded_at'],
            'location' => \sprintf('%s, %s', $metadata['latitude'], $metadata['longitude']),
            'source' => 'birdnet',
        ]);

        // Link observation to detection
        $detection->update(['observation_id' => $observation->id]);

        return \response()->json([
            'message' => 'Detection uploaded successfully',
            'detection' => $detection->fresh()->load(['species', 'observation']),
        ], 201);
    }
}
