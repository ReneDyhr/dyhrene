<?php

declare(strict_types=1);

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Services\Mail\MailDocumentKeywordScorer;

\beforeEach(function (): void {
    \config([
        'mail_classification.min_score' => 2,
        'mail_classification.receipt_keywords' => ['receipt', 'kvittering'],
        'mail_classification.payslip_keywords' => ['payslip', 'lønseddel'],
    ]);
});

\it('classifies receipt when receipt keywords dominate', function (): void {
    $scorer = new MailDocumentKeywordScorer();

    $result = $scorer->classifyFromTexts(
        ['Your receipt from the store', 'kvittering attached'],
        MailClassificationSourceEnum::Metadata,
    );

    \expect($result->confident)->toBeTrue()
        ->and($result->documentType)->toBe(MailDocumentTypeEnum::Receipt);
});

\it('classifies payslip when payslip keywords dominate', function (): void {
    $scorer = new MailDocumentKeywordScorer();

    $result = $scorer->classifyFromTexts(
        ['Your lønseddel for May', 'payslip document'],
        MailClassificationSourceEnum::Metadata,
    );

    \expect($result->confident)->toBeTrue()
        ->and($result->documentType)->toBe(MailDocumentTypeEnum::Payslip);
});

\it('returns unknown when scores are too low', function (): void {
    $scorer = new MailDocumentKeywordScorer();

    $result = $scorer->classifyFromTexts(
        ['Hello there'],
        MailClassificationSourceEnum::Metadata,
    );

    \expect($result->confident)->toBeFalse()
        ->and($result->documentType)->toBe(MailDocumentTypeEnum::Unknown);
});

\it('classifies payslip on a single strong feriepenge keyword', function (): void {
    \config([
        'mail_classification.min_score' => 2,
        'mail_classification.payslip_strong_keywords' => ['feriepenge'],
        'mail_classification.receipt_strong_keywords' => ['faktura'],
    ]);

    $scorer = new MailDocumentKeywordScorer();

    $result = $scorer->classifyFromTexts(
        ['Din udbetaling af feriepenge er gennemført'],
        MailClassificationSourceEnum::AttachmentText,
    );

    \expect($result->confident)->toBeTrue()
        ->and($result->documentType)->toBe(MailDocumentTypeEnum::Payslip);
});
