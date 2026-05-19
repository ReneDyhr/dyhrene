<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\MailClassificationSourceEnum;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Mail\DTOs\MailDocumentClassificationResult;

final class AttachmentTextMailDocumentClassifier
{
    public function __construct(
        private readonly FastmailEmailService $emailService,
        private readonly MailAttachmentTextExtractor $textExtractor,
        private readonly MailDocumentKeywordScorer $keywordScorer,
    ) {}

    public function classify(EmailMessage $message): MailDocumentClassificationResult
    {
        if ($message->attachments->isEmpty()) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::AttachmentText);
        }

        $texts = [];

        foreach ($message->attachments as $attachment) {
            try {
                $bytes = $this->emailService->downloadBlob(
                    $attachment->blobId,
                    $attachment->name,
                    $attachment->type,
                );
            } catch (\Throwable) {
                continue;
            }

            $extracted = $this->textExtractor->extractText($attachment, $bytes);

            if ($extracted !== null) {
                $texts[] = $extracted;
            }
        }

        if ($texts === []) {
            return MailDocumentClassificationResult::unknown(MailClassificationSourceEnum::AttachmentText);
        }

        return $this->keywordScorer->classifyFromTexts($texts, MailClassificationSourceEnum::AttachmentText);
    }
}
