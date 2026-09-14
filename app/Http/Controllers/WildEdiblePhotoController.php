<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WildEdiblePhoto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WildEdiblePhotoController
{
    public function __invoke(WildEdiblePhoto $photo): StreamedResponse
    {
        Gate::authorize('view', $photo);

        $stream = Storage::disk('wasabi')->readStream($photo->storage_path);

        if (!\is_resource($stream)) {
            \abort(404);
        }
        $name = \preg_replace('/[^A-Za-z0-9._-]+/', '-', $photo->original_file_name) ?: 'image';
        $name = \trim($name, '.-') ?: 'image';

        return \response()->stream(function () use ($stream): void {
            \fpassthru($stream);
            \fclose($stream);
        }, 200, [
            'Content-Type' => $photo->mime_type,
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
