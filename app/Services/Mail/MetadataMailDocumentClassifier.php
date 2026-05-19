<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\MailClassificationSourceEnum;
use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Mail\DTOs\MailDocumentClassificationResult;
use Illuminate\Support\Collection;

final class MetadataMailDocumentClassifier
{
    public function __construct(
        private readonly MailDocumentKeywordScorer $keywordScorer,
    ) {}

    /**
     * @param Collection<int, EmailAttachment> $attachments
     */
    public function classify(
        EmailSummary $summary,
        ?string $textBody,
        Collection $attachments,
    ): MailDocumentClassificationResult {
        $texts = [
            $summary->subject,
            $summary->preview ?? '',
            $textBody ?? '',
            $summary->fromDisplay(),
        ];

        foreach ($summary->from as $from) {
            if (($from['email'] ?? '') !== '') {
                $texts[] = (string) $from['email'];
            }

            if (($from['name'] ?? '') !== '') {
                $texts[] = (string) $from['name'];
            }
        }

        foreach ($attachments as $attachment) {
            $texts[] = $attachment->name;
            $texts[] = $attachment->type;
        }

        return $this->keywordScorer->classifyFromTexts($texts, MailClassificationSourceEnum::Metadata);
    }

    public function classifyMessage(EmailMessage $message): MailDocumentClassificationResult
    {
        return $this->classify($message->summary, $message->textBody, $message->attachments);
    }
}
