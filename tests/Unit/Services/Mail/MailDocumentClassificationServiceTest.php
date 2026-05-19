<?php

declare(strict_types=1);

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Mail\AttachmentTextMailDocumentClassifier;
use App\Services\Mail\MailAttachmentTextExtractor;
use App\Services\Mail\MailDocumentClassificationService;
use App\Services\Mail\MailDocumentKeywordScorer;
use App\Services\Mail\MetadataMailDocumentClassifier;
use App\Services\Mail\N8nMailDocumentClassifier;
use App\Services\Receipts\ReceiptExtractionFilePreparer;
use Carbon\CarbonImmutable;

\beforeEach(function (): void {
    \config([
        'mail_classification.min_score' => 1,
        'mail_classification.receipt_keywords' => ['receipt'],
        'mail_classification.payslip_keywords' => ['payslip'],
        'n8n.classify_webhook_url' => null,
    ]);
});

\it('uses cached classification without calling fastmail', function (): void {
    MailMessageClassification::query()->create([
        'fastmail_email_id' => 'cached-email',
        'document_type' => MailDocumentTypeEnum::Payslip,
        'confidence' => 0.9,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
    ]);

    $emailService = Mockery::mock(FastmailEmailService::class);
    $emailService->shouldNotReceive('getMessage');

    $service = new MailDocumentClassificationService(
        $emailService,
        new MetadataMailDocumentClassifier(new MailDocumentKeywordScorer()),
        new AttachmentTextMailDocumentClassifier(
            $emailService,
            new MailAttachmentTextExtractor(),
            new MailDocumentKeywordScorer(),
        ),
        new N8nMailDocumentClassifier($emailService, new ReceiptExtractionFilePreparer()),
    );

    $result = $service->classifyAndPersist('cached-email');

    \expect($result->document_type)->toBe(MailDocumentTypeEnum::Payslip)
        ->and($result->source)->toBe(MailClassificationSourceEnum::Manual);
});

\it('runs metadata tier before attachment and n8n tiers', function (): void {
    $summary = new EmailSummary(
        id: 'email-new',
        subject: 'Monthly payslip',
        from: [],
        receivedAt: CarbonImmutable::parse('2026-05-01T12:00:00Z'),
        preview: null,
        hasAttachment: true,
        mailboxIds: [],
    );

    $message = new EmailMessage(
        summary: $summary,
        from: [],
        to: [],
        textBody: null,
        htmlBody: null,
        attachments: \collect(),
    );

    $emailService = Mockery::mock(FastmailEmailService::class);
    $emailService->shouldReceive('getMessage')->once()->with('email-new')->andReturn($message);
    $emailService->shouldNotReceive('downloadBlob');

    $service = new MailDocumentClassificationService(
        $emailService,
        new MetadataMailDocumentClassifier(new MailDocumentKeywordScorer()),
        new AttachmentTextMailDocumentClassifier(
            $emailService,
            new MailAttachmentTextExtractor(),
            new MailDocumentKeywordScorer(),
        ),
        new N8nMailDocumentClassifier($emailService, new ReceiptExtractionFilePreparer()),
    );

    $stored = $service->classifyAndPersist('email-new');

    \expect($stored->document_type)->toBe(MailDocumentTypeEnum::Payslip)
        ->and($stored->source)->toBe(MailClassificationSourceEnum::Metadata);
});
