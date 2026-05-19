<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Mail\DTOs\MailDocumentClassificationResult;
use Illuminate\Support\Collection;

final class MailDocumentClassificationService
{
    public function __construct(
        private readonly FastmailEmailService $emailService,
        private readonly MetadataMailDocumentClassifier $metadataClassifier,
        private readonly AttachmentTextMailDocumentClassifier $attachmentTextClassifier,
        private readonly N8nMailDocumentClassifier $n8nClassifier,
    ) {}

    /**
     * @param list<string> $fastmailEmailIds
     *
     * @return Collection<string, MailMessageClassification>
     */
    public function getForEmailIds(array $fastmailEmailIds): Collection
    {
        if ($fastmailEmailIds === []) {
            return \collect();
        }

        return MailMessageClassification::query()
            ->whereIn('fastmail_email_id', $fastmailEmailIds)
            ->get()
            ->keyBy('fastmail_email_id');
    }

    /**
     * @param list<string> $fastmailEmailIds
     *
     * @return Collection<string, MailMessageClassification>
     */
    public function classifyMissing(array $fastmailEmailIds, bool $force = false): Collection
    {
        if ($fastmailEmailIds === []) {
            return \collect();
        }

        $existing = $this->getForEmailIds($fastmailEmailIds);

        if (!$force) {
            $toClassify = \array_values(\array_filter(
                $fastmailEmailIds,
                static fn(string $id): bool => !$existing->has($id),
            ));
        } else {
            $toClassify = $fastmailEmailIds;
        }

        foreach ($toClassify as $emailId) {
            $this->classifyAndPersist($emailId, $force);
        }

        return $this->getForEmailIds($fastmailEmailIds);
    }

    public function classifyAndPersist(string $fastmailEmailId, bool $force = false): MailMessageClassification
    {
        if (!$force) {
            $cached = MailMessageClassification::query()
                ->where('fastmail_email_id', $fastmailEmailId)
                ->first();

            if ($cached !== null) {
                return $cached;
            }
        }

        $message = $this->emailService->getMessage($fastmailEmailId);
        $result = $this->classifyMessage($message);

        return $this->persist($fastmailEmailId, $result);
    }

    public function applyManualType(string $fastmailEmailId, MailDocumentTypeEnum $type): MailMessageClassification
    {
        return $this->persist(
            $fastmailEmailId,
            new MailDocumentClassificationResult(
                documentType: $type,
                confidence: 1.0,
                source: MailClassificationSourceEnum::Manual,
                confident: true,
            ),
        );
    }

    public function classifyMessage(EmailMessage $message): MailDocumentClassificationResult
    {
        $metadata = $this->metadataClassifier->classifyMessage($message);

        if ($metadata->confident) {
            return $metadata;
        }

        if ($message->attachments->isNotEmpty()) {
            $attachmentText = $this->attachmentTextClassifier->classify($message);

            if ($attachmentText->confident) {
                return $attachmentText;
            }
        }

        if ($message->attachments->isNotEmpty()) {
            $n8n = $this->n8nClassifier->classify($message);

            if ($n8n->confident) {
                return $n8n;
            }
        }

        return new MailDocumentClassificationResult(
            documentType: MailDocumentTypeEnum::Unknown,
            confidence: 0.0,
            source: MailClassificationSourceEnum::Metadata,
            confident: true,
        );
    }

    private function persist(string $fastmailEmailId, MailDocumentClassificationResult $result): MailMessageClassification
    {
        return MailMessageClassification::query()->updateOrCreate(
            ['fastmail_email_id' => $fastmailEmailId],
            [
                'document_type' => $result->documentType,
                'confidence' => $result->confidence,
                'source' => $result->source,
                'classified_at' => \now(),
            ],
        );
    }
}
