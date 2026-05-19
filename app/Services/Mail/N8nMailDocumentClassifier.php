<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Livewire\Receipts\PdfConverter;
use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Mail\DTOs\MailDocumentClassificationResult;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

final class N8nMailDocumentClassifier
{
    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function __construct(
        private readonly FastmailEmailService $emailService,
    ) {}

    public function classify(EmailMessage $message): MailDocumentClassificationResult
    {
        $webhookUrl = \config('n8n.classify_webhook_url');

        if (!\is_string($webhookUrl) || \trim($webhookUrl) === '') {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        $attachment = $this->firstClassifiableAttachment($message);

        if ($attachment === null) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        try {
            $bytes = $this->emailService->downloadBlob(
                $attachment->blobId,
                $attachment->name,
                $attachment->type,
            );
        } catch (\Throwable) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        $uploadPayload = $this->prepareUploadPayload($attachment, $bytes);

        if ($uploadPayload === null) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        try {
            $response = Http::timeout(120)
                ->attach('File', $uploadPayload['contents'], $uploadPayload['filename'])
                ->post($webhookUrl);

            if ($uploadPayload['cleanup']) {
                @\unlink($uploadPayload['path']);
            }

            if (!$response->successful()) {
                return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
            }

            $normalized = $this->normalizeResponseData($response->json());

            if ($normalized === null) {
                return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
            }

            return $this->parseResponse($normalized);
        } catch (\Throwable) {
            if ($uploadPayload['cleanup'] && $uploadPayload['path'] !== '') {
                @\unlink($uploadPayload['path']);
            }

            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }
    }

    private function firstClassifiableAttachment(EmailMessage $message): ?EmailAttachment
    {
        foreach ($message->attachments as $attachment) {
            if ($this->isClassifiableAttachment($attachment)) {
                return $attachment;
            }
        }

        return null;
    }

    private function isClassifiableAttachment(EmailAttachment $attachment): bool
    {
        $mime = \mb_strtolower($attachment->type);
        $name = \mb_strtolower($attachment->name);

        if (\in_array($mime, self::IMAGE_MIMES, true)) {
            return true;
        }

        if (\str_contains($mime, 'pdf') || \str_ends_with($name, '.pdf')) {
            return true;
        }

        return false;
    }

    /**
     * @return null|array{contents: string, filename: string, path: string, cleanup: bool}
     */
    private function prepareUploadPayload(EmailAttachment $attachment, string $bytes): ?array
    {
        $mime = \mb_strtolower($attachment->type);
        $filename = $attachment->name !== '' ? $attachment->name : 'attachment';

        if (\in_array($mime, self::IMAGE_MIMES, true) || \str_starts_with($mime, 'image/')) {
            return [
                'contents' => $bytes,
                'filename' => $filename,
                'path' => '',
                'cleanup' => false,
            ];
        }

        $tmpPdf = \sys_get_temp_dir() . '/' . \uniqid('mail_n8n_', true) . '.pdf';

        if (\file_put_contents($tmpPdf, $bytes) === false) {
            return null;
        }

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
                return null;
            }

            $contents = \file_get_contents($path);

            if ($contents === false || $contents === '') {
                return null;
            }

            $jpgName = \pathinfo($filename, \PATHINFO_FILENAME) . '.jpg';
            $cleanup = $converted instanceof File;

            return [
                'contents' => $contents,
                'filename' => $jpgName,
                'path' => $path,
                'cleanup' => $cleanup,
            ];
        } catch (\Throwable) {
            @\unlink($tmpPdf);

            return null;
        }
    }

    /**
     * @return null|array<string, mixed>
     */
    private function normalizeResponseData(mixed $data): ?array
    {
        if (!\is_array($data)) {
            return null;
        }

        $normalized = [];

        foreach ($data as $key => $value) {
            if (\is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parseResponse(array $data): MailDocumentClassificationResult
    {
        $typeRaw = $data['document_type'] ?? $data['documentType'] ?? null;

        if (!\is_string($typeRaw)) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        $type = MailDocumentTypeEnum::tryFrom(\mb_strtolower(\trim($typeRaw)));

        if ($type === null || $type === MailDocumentTypeEnum::Unknown) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        $confidence = 0.0;

        if (isset($data['confidence']) && \is_numeric($data['confidence'])) {
            $confidence = (float) $data['confidence'];
        }

        $minConfidenceConfig = \config('mail_classification.n8n_min_confidence', 0.5);
        $minConfidence = \is_float($minConfidenceConfig) ? $minConfidenceConfig : 0.5;

        if ($confidence < $minConfidence) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        return new MailDocumentClassificationResult(
            documentType: $type,
            confidence: $confidence,
            source: MailClassificationSourceEnum::N8n,
            confident: true,
        );
    }
}
