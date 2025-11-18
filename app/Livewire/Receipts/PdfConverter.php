<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $localPdfPath = null;
        $realPath = false;

        try {
            // Get the PDF file path - handle both local and remote storage
            $realPath = $file->getRealPath();

            if ($realPath !== false && \file_exists($realPath)) {
                // File is available locally
                $localPdfPath = $realPath;
            } else {
                // File is stored on remote storage (Wasabi), download it first
                $localPdfPath = \sys_get_temp_dir() . '/' . \uniqid('pdf_download_', true) . '.pdf';

                // Try to get file contents from UploadedFile
                $fileContents = $file->get();

                // If that fails, try to get from Wasabi storage using pathname
                if ($fileContents === false || $fileContents === '') {
                    $pathname = $file->getPathname();

                    if (\Storage::disk('wasabi')->exists($pathname)) {
                        $storageContents = \Storage::disk('wasabi')->get($pathname);

                        if ($storageContents !== false && $storageContents !== '') {
                            $fileContents = $storageContents;
                        }
                    }
                }

                if ($fileContents === false || $fileContents === '') {
                    throw new \RuntimeException('Unable to retrieve PDF file contents from remote storage.');
                }

                \file_put_contents($localPdfPath, $fileContents);
            }

            $pdf = new Pdf($localPdfPath);
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

            // Cleanup downloaded PDF if it was downloaded from remote storage
            if ($realPath === false || !\file_exists($realPath)) {
                @\unlink($localPdfPath);
            }

            return new File($tmpPath);
        } catch (\Throwable $e) {
            // Cleanup downloaded PDF in case of error (only if it was downloaded, not if it was local)
            if ($localPdfPath !== null && $realPath === false && \file_exists($localPdfPath)) {
                @\unlink($localPdfPath);
            }

            throw new \RuntimeException('Failed to convert PDF to image: ' . $e->getMessage(), 0, $e);
        }
    }
}
