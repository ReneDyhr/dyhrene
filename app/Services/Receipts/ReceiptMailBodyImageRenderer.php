<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;

final class ReceiptMailBodyImageRenderer
{
    private const IMAGE_WIDTH = 820;

    private const LINE_HEIGHT = 18;

    private const FONT_SIZE = 14;

    private const PADDING = 24;

    private const MAX_LINES = 500;

    private const CHARS_PER_LINE = 92;

    public function renderToJpegBytes(EmailMessage $message): string
    {
        $text = $this->resolveDisplayText($message);

        if ($text === '') {
            throw new ReceiptExtractionException('No email body content to save as receipt image.');
        }

        if (!\extension_loaded('imagick') || !\class_exists(\Imagick::class)) {
            throw new ReceiptExtractionException('Imagick is required to save email body as an image.');
        }

        $lines = $this->wrapLines($text, self::CHARS_PER_LINE);
        $lineCount = \min(\count($lines), self::MAX_LINES);
        $height = self::PADDING * 2 + $lineCount * self::LINE_HEIGHT;

        $image = new \Imagick();
        $image->newImage(self::IMAGE_WIDTH, $height, new \ImagickPixel('white'));
        $image->setImageFormat('jpeg');
        $image->setImageCompressionQuality(88);

        $draw = new \ImagickDraw();
        $draw->setFontSize(self::FONT_SIZE);
        $draw->setFillColor(new \ImagickPixel('#111111'));
        $this->applyFont($draw);

        $y = self::PADDING + self::FONT_SIZE;

        for ($i = 0; $i < $lineCount; $i++) {
            $image->annotateImage($draw, (float) self::PADDING, (float) $y, 0, $lines[$i]);
            $y += self::LINE_HEIGHT;
        }

        if (\count($lines) > self::MAX_LINES) {
            $image->annotateImage($draw, (float) self::PADDING, (float) $y, 0, '… (truncated)');
        }

        $bytes = $image->getImageBlob();
        $image->clear();
        $image->destroy();

        if ($bytes === '') {
            throw new ReceiptExtractionException('Failed to render email body image.');
        }

        return $bytes;
    }

    private function resolveDisplayText(EmailMessage $message): string
    {
        if ($message->textBody !== null && \trim($message->textBody) !== '') {
            return $this->normalizeText($message->textBody);
        }

        if ($message->htmlBody !== null && \trim($message->htmlBody) !== '') {
            $decoded = \html_entity_decode($message->htmlBody, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

            return $this->normalizeText(\strip_tags($decoded));
        }

        $preview = $message->summary->preview;

        if ($preview !== null && \trim($preview) !== '') {
            return $this->normalizeText($preview);
        }

        return '';
    }

    private function normalizeText(string $text): string
    {
        $text = \str_replace(["\r\n", "\r"], "\n", $text);
        $text = \preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return \trim($text);
    }

    /**
     * @return list<string>
     */
    private function wrapLines(string $text, int $width): array
    {
        $lines = [];
        $paragraphs = \explode("\n", $text);

        foreach ($paragraphs as $paragraph) {
            $paragraph = \trim($paragraph);

            if ($paragraph === '') {
                $lines[] = '';

                continue;
            }

            $wrapped = \wordwrap($paragraph, $width, "\n", true);
            $split = \explode("\n", $wrapped);

            foreach ($split as $line) {
                $lines[] = $line;
            }
        }

        return $lines === [] ? [''] : $lines;
    }

    private function applyFont(\ImagickDraw $draw): void
    {
        foreach (['DejaVu-Sans', 'Liberation-Sans', 'Arial', 'Helvetica'] as $font) {
            try {
                $draw->setFont($font);

                return;
            } catch (\Throwable) {
                continue;
            }
        }
    }
}
