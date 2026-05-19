<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Services\Fastmail\DTOs\EmailAttachment;
use Smalot\PdfParser\Parser as PdfParser;

final class MailAttachmentTextExtractor
{
    public function extractText(EmailAttachment $attachment, string $bytes): ?string
    {
        $mime = \mb_strtolower($attachment->type);

        if (\str_contains($mime, 'pdf') || \str_ends_with(\mb_strtolower($attachment->name), '.pdf')) {
            return $this->extractFromPdf($bytes);
        }

        if ($mime === 'text/html' || \str_ends_with(\mb_strtolower($attachment->name), '.html')) {
            return $this->extractFromHtml($bytes);
        }

        if ($this->isPlainTextMime($mime)) {
            return $this->extractFromPlainText($bytes);
        }

        return null;
    }

    private function extractFromPdf(string $bytes): ?string
    {
        if ($bytes === '') {
            return null;
        }

        $tmpPath = \sys_get_temp_dir() . '/' . \uniqid('mail_pdf_', true) . '.pdf';

        try {
            if (\file_put_contents($tmpPath, $bytes) === false) {
                return null;
            }

            $parser = new PdfParser();
            $pdf = $parser->parseFile($tmpPath);
            $text = \trim($pdf->getText());

            return $text !== '' ? $text : null;
        } catch (\Throwable) {
            return null;
        } finally {
            if (\file_exists($tmpPath)) {
                @\unlink($tmpPath);
            }
        }
    }

    private function extractFromPlainText(string $bytes): ?string
    {
        if ($bytes === '') {
            return null;
        }

        $text = @\mb_convert_encoding($bytes, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        if (!\is_string($text)) {
            return null;
        }

        $trimmed = \trim($text);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function extractFromHtml(string $bytes): ?string
    {
        $raw = $this->extractFromPlainText($bytes);

        if ($raw === null) {
            return null;
        }

        $decoded = \html_entity_decode($raw, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = \trim(\strip_tags($decoded));

        return $text !== '' ? $text : null;
    }

    private function isPlainTextMime(string $mime): bool
    {
        return \str_starts_with($mime, 'text/')
            || $mime === 'application/json'
            || $mime === 'application/xml';
    }
}
