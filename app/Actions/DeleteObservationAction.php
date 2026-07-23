<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BirdnetDetection;
use App\Models\Observation;
use Illuminate\Support\Facades\Storage;

class DeleteObservationAction
{
    public function handle(Observation $observation): void
    {
        // Collect audio paths before deleting detections
        $audioPaths = $observation->birdnetDetections()
            ->whereNotNull('audio_path')
            ->where('audio_path', '!=', '')
            ->pluck('audio_path')
            ->toArray();

        // Delete birdnet detections for this observation
        $observation->birdnetDetections()->delete();

        // Delete the observation
        $observation->delete();

        // Delete audio files that are no longer referenced by any detection
        foreach ($audioPaths as $path) {
            if (!\is_string($path)) {
                continue;
            }

            $stillUsed = BirdnetDetection::query()
                ->where('audio_path', $path)
                ->exists();

            if (!$stillUsed) {
                Storage::disk('wasabi')->delete($path);
            }
        }
    }
}
