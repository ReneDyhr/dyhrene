<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Spatie\PdfToImage\Pdf;

class PdfConverter
{
    /**
     * Converts a PDF (UploadedFile) to a JPG image (File) and returns the File instance.
     * If the file is not a PDF, returns the original UploadedFile.
     */
    public static function convertToJpg(UploadedFile $file): File | UploadedFile
    {
        $extension = \strtolower($file->getClientOriginalExtension());

        if ($extension !== 'pdf') {
            return $file;
        }
        $tmpPath = \sys_get_temp_dir() . '/' . \uniqid('pdf2img_', true) . '.jpg';

        try {
            $pdf = new Pdf($file->getRealPath());
            $pdf->selectPage(1)->format(\Spatie\PdfToImage\Enums\OutputFormat::Jpg)->save($tmpPath);

            return new File($tmpPath);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to convert PDF to image: ' . $e->getMessage(), 0, $e);
        }
    }
}
