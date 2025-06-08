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
            $pageCount = $pdf->pageCount();
            $images = [];

            for ($i = 1; $i <= $pageCount; $i++) {
                $pageImgPath = \sys_get_temp_dir() . '/' . \uniqid('pdf2img_page_', true) . '.jpg';
                $pdf->selectPage($i)->format(\Spatie\PdfToImage\Enums\OutputFormat::Jpg)->save($pageImgPath);
                $images[] = $pageImgPath;
            }
            // Combine all page images vertically into one image
            $imagick = new \Imagick();

            foreach ($images as $img) {
                $imagick->readImage($img);
            }
            $imagick->resetIterator();
            $combined = $imagick->appendImages(true); // true = vertical
            $combined->setImageFormat('jpg');
            $combined->writeImage($tmpPath);

            // Cleanup page images
            foreach ($images as $img) {
                @\unlink($img);
            }

            return new File($tmpPath);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to convert PDF to image: ' . $e->getMessage(), 0, $e);
        }
    }
}
