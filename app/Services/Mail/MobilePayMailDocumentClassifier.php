<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Mail\DTOs\MailDocumentClassificationResult;

/**
 * Classifies forwarded MobilePay screenshot PDFs (numeric filename, e.g. 4527847806.pdf).
 *
 * Outgoing (Sent / Paid) → receipt; incoming (Received) → payslip.
 */
final class MobilePayMailDocumentClassifier
{
    public function __construct(
        private readonly FastmailEmailService $emailService,
        private readonly MailAttachmentTextExtractor $textExtractor,
    ) {}

    public function classify(EmailMessage $message): MailDocumentClassificationResult
    {
        $attachment = $this->findMobilePayPdf($message);

        if ($attachment === null) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::MobilePay);
        }

        try {
            $bytes = $this->emailService->downloadBlob(
                $attachment->blobId,
                $attachment->name,
                $attachment->type,
            );
        } catch (\Throwable) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::MobilePay);
        }

        $text = $this->textExtractor->extractText($attachment, $bytes);

        if ($text !== null) {
            $fromText = $this->classifyFromText($text);

            if ($fromText !== null) {
                return $fromText;
            }
        }

        $dimensions = $this->readPdfPageDimensions($bytes);

        if ($dimensions === null) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::MobilePay);
        }

        return $this->classifyFromDimensions($dimensions);
    }

    private function findMobilePayPdf(EmailMessage $message): ?EmailAttachment
    {
        foreach ($message->attachments as $attachment) {
            if (!$this->isMobilePayPdfAttachment($attachment)) {
                continue;
            }

            return $attachment;
        }

        return null;
    }

    private function isMobilePayPdfAttachment(EmailAttachment $attachment): bool
    {
        $mime = \mb_strtolower($attachment->type);
        $name = $attachment->name;

        if (!\str_contains($mime, 'pdf') && !\str_ends_with(\mb_strtolower($name), '.pdf')) {
            return false;
        }

        return (bool) \preg_match('/^\d+\.pdf$/i', $name);
    }

    private function classifyFromText(string $text): ?MailDocumentClassificationResult
    {
        $haystack = \mb_strtolower($text);

        if ($this->matchesIncoming($haystack)) {
            return $this->confidentResult(MailDocumentTypeEnum::Payslip);
        }

        if ($this->matchesOutgoing($haystack)) {
            return $this->confidentResult(MailDocumentTypeEnum::Receipt);
        }

        return null;
    }

    private function matchesIncoming(string $haystack): bool
    {
        foreach ($this->incomingKeywords() as $keyword) {
            if (\str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function matchesOutgoing(string $haystack): bool
    {
        foreach ($this->outgoingKeywords() as $keyword) {
            if (\str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return null|array{width: int, height: int}
     */
    private function readPdfPageDimensions(string $bytes): ?array
    {
        if ($bytes === '' || !\extension_loaded('imagick') || !\class_exists(\Imagick::class)) {
            return null;
        }

        $tmpPath = \sys_get_temp_dir() . '/' . \uniqid('mobilepay_pdf_', true) . '.pdf';

        try {
            if (\file_put_contents($tmpPath, $bytes) === false) {
                return null;
            }

            $image = new \Imagick();
            $image->readImage($tmpPath . '[0]');
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $image->clear();

            if ($width < 1 || $height < 1) {
                return null;
            }

            return ['width' => $width, 'height' => $height];
        } catch (\Throwable) {
            return null;
        } finally {
            if (\file_exists($tmpPath)) {
                @\unlink($tmpPath);
            }
        }
    }

    /**
     * @param array{width: int, height: int} $dimensions
     */
    private function classifyFromDimensions(array $dimensions): MailDocumentClassificationResult
    {
        $minSentWidth = $this->intConfig('mail_classification.mobilepay_sent_min_width', 1500);
        $minSentHeight = $this->intConfig('mail_classification.mobilepay_sent_min_height', 1900);

        $isOutgoing = $dimensions['width'] >= $minSentWidth
            || $dimensions['height'] >= $minSentHeight;

        return $this->confidentResult(
            $isOutgoing ? MailDocumentTypeEnum::Receipt : MailDocumentTypeEnum::Payslip,
        );
    }

    private function confidentResult(MailDocumentTypeEnum $type): MailDocumentClassificationResult
    {
        return new MailDocumentClassificationResult(
            documentType: $type,
            confidence: 0.85,
            source: MailClassificationSourceEnum::MobilePay,
            confident: true,
        );
    }

    private function intConfig(string $key, int $default): int
    {
        $value = \config($key, $default);

        return \is_int($value) ? $value : $default;
    }

    /**
     * @return list<string>
     */
    private function incomingKeywords(): array
    {
        /** @var list<string> $keywords */
        $keywords = \config('mail_classification.mobilepay_incoming_keywords', []);

        return $keywords;
    }

    /**
     * @return list<string>
     */
    private function outgoingKeywords(): array
    {
        /** @var list<string> $keywords */
        $keywords = \config('mail_classification.mobilepay_outgoing_keywords', []);

        return $keywords;
    }
}
