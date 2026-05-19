<?php

declare(strict_types=1);

use App\Enums\MailDocumentTypeEnum;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Mail\MailDocumentKeywordScorer;
use App\Services\Mail\MetadataMailDocumentClassifier;
use Carbon\CarbonImmutable;

\beforeEach(function (): void {
    \config([
        'mail_classification.min_score' => 1,
        'mail_classification.receipt_keywords' => ['receipt'],
        'mail_classification.payslip_keywords' => ['payslip'],
    ]);
});

\it('classifies from email subject metadata', function (): void {
    $classifier = new MetadataMailDocumentClassifier(new MailDocumentKeywordScorer());

    $summary = new EmailSummary(
        id: 'email-1',
        subject: 'Your receipt is ready',
        from: [['name' => 'Store', 'email' => 'store@example.com']],
        receivedAt: CarbonImmutable::parse('2026-05-01T12:00:00Z'),
        preview: null,
        hasAttachment: false,
        mailboxIds: ['mbox-inbox'],
    );

    $result = $classifier->classify($summary, null, \collect());

    \expect($result->documentType)->toBe(MailDocumentTypeEnum::Receipt)
        ->and($result->confident)->toBeTrue();
});
