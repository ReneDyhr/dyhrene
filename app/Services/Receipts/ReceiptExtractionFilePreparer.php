<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Livewire\Receipts\PdfConverter;
use App\Services\Fastmail\DTOs\EmailAttachment;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

final class ReceiptExtractionFilePreparer
{
    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * @return array{contents: string, filename: string, cleanup: callable(): void}
     */
    public function fromAttachmentBytes(EmailAttachment $attachment, string $bytes): array
    {
        $mime = \mb_strtolower($attachment->type);
        $filename = $attachment->name !== '' ? $attachment->name : 'attachment';

        if (\in_array($mime, self::IMAGE_MIMES, true) || \str_starts_with($mime, 'image/')) {
            return [
                'contents' => $bytes,
                'filename' => $filename,
                'cleanup' => static function (): void {},
            ];
        }

        return $this->fromPdfBytes($bytes, $filename);
    }

    /**
     * @return array{contents: string, filename: string, cleanup: callable(): void}
     */
    public function fromPdfBytes(string $bytes, string $filename): array
    {
        $tmpPdf = \sys_get_temp_dir() . '/' . \uniqid('receipt_extract_', true) . '.pdf';

        if (\file_put_contents($tmpPdf, $bytes) === false) {
            throw new \InvalidArgumentException('Failed to write temporary PDF file.');
        }

        $cleanupPath = '';

        try {
            $uploaded = new UploadedFile(
                $tmpPdf,
                $filename,
                'application/pdf',
                null,
                true,
            );
            $converted = PdfConverter::convertToJpg($uploaded);
            $path = $converted->getRealPath();

            if ($path === false) {
                throw new \InvalidArgumentException('Failed to convert PDF for extraction.');
            }

            $contents = \file_get_contents($path);

            if ($contents === false || $contents === '') {
                throw new \InvalidArgumentException('Failed to read converted image.');
            }

            $jpgName = \pathinfo($filename, \PATHINFO_FILENAME) . '.jpg';

            if ($converted instanceof File) {
                $cleanupPath = $path;
            }

            return [
                'contents' => $contents,
                'filename' => $jpgName,
                'cleanup' => static function () use ($tmpPdf, $cleanupPath): void {
                    if ($cleanupPath !== '' && \file_exists($cleanupPath)) {
                        @\unlink($cleanupPath);
                    }

                    if (\file_exists($tmpPdf)) {
                        @\unlink($tmpPdf);
                    }
                },
            ];
        } catch (\Throwable $e) {
            if (\file_exists($tmpPdf)) {
                @\unlink($tmpPdf);
            }

            throw $e;
        }
    }

    /**
     * @return array{contents: string, filename: string, cleanup: callable(): void}
     */
    public function fromPlainText(string $text, string $filename = 'email-body.txt'): array
    {
        return [
            'contents' => $text,
            'filename' => $filename,
            'cleanup' => static function (): void {},
        ];
    }
}
