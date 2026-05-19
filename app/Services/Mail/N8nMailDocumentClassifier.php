<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Mail\DTOs\MailDocumentClassificationResult;
use App\Services\Receipts\ReceiptExtractionFilePreparer;
use Illuminate\Support\Facades\Http;

final class N8nMailDocumentClassifier
{
    public function __construct(
        private readonly FastmailEmailService $emailService,
        private readonly ReceiptExtractionFilePreparer $filePreparer,
    ) {}

    public function classify(EmailMessage $message): MailDocumentClassificationResult
    {
        $webhookUrl = \config('n8n.classify_webhook_url');

        if (!\is_string($webhookUrl) || \trim($webhookUrl) === '') {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        $attachment = ClassifiableMailAttachment::firstFromMessage($message);

        if ($attachment === null) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        try {
            $bytes = $this->emailService->downloadBlob(
                $attachment->blobId,
                $attachment->name,
                $attachment->type,
            );
            $uploadPayload = $this->filePreparer->fromAttachmentBytes($attachment, $bytes);
        } catch (\Throwable) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
        }

        try {
            $response = Http::timeout(120)
                ->attach('File', $uploadPayload['contents'], $uploadPayload['filename'])
                ->post($webhookUrl);

            ($uploadPayload['cleanup'])();

            if (!$response->successful()) {
                return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
            }

            $normalized = $this->normalizeResponseData($response->json());

            if ($normalized === null) {
                return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
            }

            return $this->parseResponse($normalized);
        } catch (\Throwable) {
            ($uploadPayload['cleanup'])();

            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::N8n);
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
