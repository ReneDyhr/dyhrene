<?php

declare(strict_types=1);

namespace App\Support\WildEdibles;

use Illuminate\Http\UploadedFile;

final class ImageUploadGuard
{
    public static function isAnimated(UploadedFile $file): bool
    {
        $path = $file->getRealPath();
        $contents = $path === false ? false : \file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        return match (\strtolower($file->getClientOriginalExtension())) {
            'gif' => \substr_count($contents, "\x21\xF9\x04") > 1,
            'png' => \str_contains($contents, 'acTL'),
            'webp' => \str_contains($contents, 'ANIM'),
            default => false,
        };
    }
}
