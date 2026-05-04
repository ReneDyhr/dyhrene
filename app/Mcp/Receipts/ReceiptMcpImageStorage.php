<?php

declare(strict_types=1);

namespace App\Mcp\Receipts;

use App\Livewire\Receipts\PdfConverter;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ReceiptMcpImageStorage
{
    public const MAX_BYTES = 15_728_640; // 15 MiB

    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    /**
     * @return non-empty-string Storage path on the wasabi disk
     */
    public static function storeFromBase64(string $rawBase64, string $imageMimeType): string
    {
        $binary = self::decodeBase64($rawBase64);

        if (\strlen($binary) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Decoded image exceeds maximum allowed size (15 MiB).');
        }

        $mime = \strtolower(\trim($imageMimeType));

        if (!\in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('image_mime_type must be one of: image/jpeg, image/png, application/pdf.');
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        };

        $tmpPath = \sys_get_temp_dir() . '/' . \uniqid('mcp_receipt_', true) . '.' . $extension;

        if (@\file_put_contents($tmpPath, $binary) === false) {
            throw new \InvalidArgumentException('Failed to write temporary image file.');
        }

        try {
            $uploaded = new UploadedFile($tmpPath, 'receipt.' . $extension, $mime, null, true);
            $imageForSave = PdfConverter::convertToJpg($uploaded);

            if ($imageForSave instanceof UploadedFile) {
                $path = $imageForSave->store('receipts', 'wasabi');
            } else {
                $path = Storage::disk('wasabi')->putFile('receipts', $imageForSave);
            }

            if ($path === false || $path === '') {
                throw new \InvalidArgumentException('Failed to save receipt image to storage.');
            }

            if ($imageForSave instanceof File && \file_exists($imageForSave->getPathname())) {
                @\unlink($imageForSave->getPathname());
            }

            return $path;
        } finally {
            if (\file_exists($tmpPath)) {
                @\unlink($tmpPath);
            }
        }
    }

    /**
     * @return non-empty-string binary
     */
    public static function decodeBase64(string $rawBase64): string
    {
        $trimmed = \trim($rawBase64);

        if (\preg_match('/^data:[^;]+;base64,(.+)$/s', $trimmed, $m) === 1) {
            $trimmed = $m[1];
        }

        $trimmed = \preg_replace('/\s+/', '', $trimmed) ?? $trimmed;
        $binary = \base64_decode($trimmed, true);

        if ($binary === false || $binary === '') {
            throw new \InvalidArgumentException('Invalid base64 image data.');
        }

        return $binary;
    }
}
