<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Livewire\Receipts\PdfConverter;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ReceiptAttachmentStorage
{
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf',
    ];

    /**
     * Store bytes already prepared for receipt image display (e.g. post-n8n JPG).
     *
     * @return non-empty-string Storage path on the wasabi disk
     */
    public function storePreparedImageBytes(string $bytes, string $filename): string
    {
        if ($bytes === '') {
            throw new \InvalidArgumentException('Empty attachment content.');
        }

        $safeName = $filename !== '' ? $filename : 'receipt.jpg';
        $tmpPath = \sys_get_temp_dir() . '/' . \uniqid('receipt_mail_', true) . '.jpg';

        if (@\file_put_contents($tmpPath, $bytes) === false) {
            throw new \InvalidArgumentException('Failed to write temporary attachment file.');
        }

        try {
            $uploaded = new UploadedFile(
                $tmpPath,
                $safeName,
                'image/jpeg',
                null,
                true,
            );
            $path = $uploaded->store('receipts', 'wasabi');

            if ($path === false || $path === '') {
                throw new \InvalidArgumentException('Failed to save receipt image to storage.');
            }

            return $path;
        } finally {
            if (\file_exists($tmpPath)) {
                @\unlink($tmpPath);
            }
        }
    }

    /**
     * @return non-empty-string Storage path on the wasabi disk
     */
    public function storeFromBytes(string $bytes, string $mimeType, string $filename): string
    {
        $mime = \mb_strtolower(\trim($mimeType));

        if (!\in_array($mime, self::ALLOWED_MIMES, true) && !\str_starts_with($mime, 'image/')) {
            throw new \InvalidArgumentException('Unsupported attachment type for receipt storage.');
        }

        if ($bytes === '') {
            throw new \InvalidArgumentException('Empty attachment content.');
        }

        $extension = match (true) {
            \str_contains($mime, 'pdf') => 'pdf',
            \str_contains($mime, 'png') => 'png',
            default => 'jpg',
        };

        $safeName = $filename !== '' ? $filename : 'attachment.' . $extension;
        $tmpPath = \sys_get_temp_dir() . '/' . \uniqid('receipt_mail_', true) . '.' . $extension;

        if (@\file_put_contents($tmpPath, $bytes) === false) {
            throw new \InvalidArgumentException('Failed to write temporary attachment file.');
        }

        try {
            $uploaded = new UploadedFile(
                $tmpPath,
                $safeName,
                $mimeType !== '' ? $mimeType : 'application/octet-stream',
                null,
                true,
            );
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
}
