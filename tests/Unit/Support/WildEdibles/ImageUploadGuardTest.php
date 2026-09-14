<?php

declare(strict_types=1);

use App\Support\WildEdibles\ImageUploadGuard;
use Illuminate\Http\UploadedFile;

\covers(ImageUploadGuard::class);

\it('detects animated GIF, PNG, and WebP signatures', function (): void {
    $gif = UploadedFile::fake()->createWithContent('animated.gif', "GIF89a\x21\xF9\x04frame1\x21\xF9\x04frame2");
    $png = UploadedFile::fake()->createWithContent('animated.png', "\x89PNG\r\n\x1a\nacTL");
    $webp = UploadedFile::fake()->createWithContent('animated.webp', 'RIFFxxxxWEBPVP8XANIM');

    \expect(ImageUploadGuard::isAnimated($gif))->toBeTrue()
        ->and(ImageUploadGuard::isAnimated($png))->toBeTrue()
        ->and(ImageUploadGuard::isAnimated($webp))->toBeTrue();
});
