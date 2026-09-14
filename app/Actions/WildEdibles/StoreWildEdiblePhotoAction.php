<?php

declare(strict_types=1);

namespace App\Actions\WildEdibles;

use App\Models\User;
use App\Models\WildEdible;
use App\Models\WildEdiblePhoto;
use App\Support\WildEdibles\ImageUploadGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StoreWildEdiblePhotoAction
{
    public function handle(User $user, WildEdible $wildEdible, UploadedFile $file): WildEdiblePhoto
    {
        if ($wildEdible->user_id !== $user->id || $wildEdible->trashed()) {
            throw new \Illuminate\Auth\Access\AuthorizationException();
        }

        $extension = \strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        if (!\in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
            || !\in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)
            || ($file->getSize() !== false && $file->getSize() > 10 * 1024 * 1024)
            || ImageUploadGuard::isAnimated($file)) {
            throw ValidationException::withMessages(['photo' => 'The uploaded image is invalid or unsupported.']);
        }

        $path = 'wild-edibles/' . $wildEdible->user_id . '/' . Str::uuid()->toString() . '.' . $extension;
        $stream = \fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new \RuntimeException('The uploaded image could not be read.');
        }

        $metadata = [];
        $dimensions = @\getimagesize($file->getRealPath());

        if (\is_array($dimensions)) {
            $metadata['width'] = $dimensions[0];
            $metadata['height'] = $dimensions[1];
        }

        if ($extension === 'jpg' || $extension === 'jpeg') {
            $exif = @\exif_read_data($file->getRealPath(), null, true);

            if (\is_array($exif)) {
                $metadata['exif'] = $exif;
            }
        }

        try {
            if (Storage::disk('wasabi')->put($path, $stream) !== true) {
                throw new \RuntimeException('The uploaded image could not be stored.');
            }

            return $wildEdible->photos()->create([
                'storage_path' => $path,
                'original_file_name' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => $file->getSize() ?: 0,
                'metadata' => $metadata === [] ? null : $metadata,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('wasabi')->delete($path);

            throw $exception;
        } finally {
            \fclose($stream);
        }
    }
}
