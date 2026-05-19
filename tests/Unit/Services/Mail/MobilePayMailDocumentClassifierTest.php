<?php

declare(strict_types=1);

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Mail\MailAttachmentTextExtractor;
use App\Services\Mail\MobilePayMailDocumentClassifier;
use Carbon\CarbonImmutable;

\beforeEach(function (): void {
    \config([
        'mail_classification.mobilepay_sent_min_width' => 500,
        'mail_classification.mobilepay_sent_min_height' => 650,
        'mail_classification.mobilepay_incoming_keywords' => ['received', 'modtaget'],
        'mail_classification.mobilepay_outgoing_keywords' => ['sent', 'paid by', 'withdrawn from'],
    ]);
});

\it('classifies received MobilePay screenshot PDF as payslip', function (): void {
    if (!\extension_loaded('imagick') || !\is_readable('/tmp/StowOt95dkBk.pdf')) {
        $this->markTestSkipped('Imagick or sample MobilePay PDF not available.');
    }

    $bytes = \file_get_contents('/tmp/StowOt95dkBk.pdf');
    \assert(\is_string($bytes));

    $emailService = Mockery::mock(FastmailEmailService::class);
    $emailService->shouldReceive('downloadBlob')->once()->andReturn($bytes);

    $classifier = new MobilePayMailDocumentClassifier($emailService, new MailAttachmentTextExtractor());
    $result = $classifier->classify(\mobilePayTestMessage('4527847806.pdf'));

    \expect($result->confident)->toBeTrue()
        ->and($result->documentType)->toBe(MailDocumentTypeEnum::Payslip)
        ->and($result->source)->toBe(MailClassificationSourceEnum::MobilePay);
});

\it('classifies sent MobilePay screenshot PDF as receipt', function (): void {
    if (!\extension_loaded('imagick') || !\is_readable('/tmp/StowQZyCui8N.pdf')) {
        $this->markTestSkipped('Imagick or sample MobilePay PDF not available.');
    }

    $bytes = \file_get_contents('/tmp/StowQZyCui8N.pdf');
    \assert(\is_string($bytes));

    $emailService = Mockery::mock(FastmailEmailService::class);
    $emailService->shouldReceive('downloadBlob')->once()->andReturn($bytes);

    $classifier = new MobilePayMailDocumentClassifier($emailService, new MailAttachmentTextExtractor());
    $result = $classifier->classify(\mobilePayTestMessage('4520428867.pdf'));

    \expect($result->confident)->toBeTrue()
        ->and($result->documentType)->toBe(MailDocumentTypeEnum::Receipt)
        ->and($result->source)->toBe(MailClassificationSourceEnum::MobilePay);
});

\it('returns unknown for non MobilePay attachments', function (): void {
    $emailService = Mockery::mock(FastmailEmailService::class);
    $emailService->shouldNotReceive('downloadBlob');

    $classifier = new MobilePayMailDocumentClassifier($emailService, new MailAttachmentTextExtractor());

    $summary = new EmailSummary(
        id: 'email-1',
        subject: 'Invoice',
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
        attachments: \collect([
            new EmailAttachment('2', 'blob', 'invoice.pdf', 'application/pdf', 1000),
        ]),
    );

    $result = $classifier->classify($message);

    \expect($result->confident)->toBeFalse()
        ->and($result->documentType)->toBe(MailDocumentTypeEnum::Unknown);
});

function mobilePayTestMessage(string $filename): EmailMessage
{
    $summary = new EmailSummary(
        id: 'email-mp',
        subject: \pathinfo($filename, \PATHINFO_FILENAME),
        from: [],
        receivedAt: CarbonImmutable::parse('2026-05-19T07:14:00Z'),
        preview: null,
        hasAttachment: true,
        mailboxIds: [],
    );

    return new EmailMessage(
        summary: $summary,
        from: [],
        to: [],
        textBody: null,
        htmlBody: null,
        attachments: \collect([
            new EmailAttachment('2', 'blob-1', $filename, 'application/pdf', 100_000),
        ]),
    );
}
