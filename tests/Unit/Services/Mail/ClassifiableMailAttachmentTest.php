<?php

declare(strict_types=1);

use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Mail\ClassifiableMailAttachment;

\covers(ClassifiableMailAttachment::class);

function makeAttachment(string $name, string $type, string $blobId = 'blob-1'): EmailAttachment
{
    return new EmailAttachment(
        partId: '1',
        blobId: $blobId,
        name: $name,
        type: $type,
        size: 1000,
    );
}

\it('classifies a pdf attachment as classifiable', function (): void {
    $attachment = \makeAttachment('kvittering.pdf', 'application/pdf');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeTrue();
});

\it('classifies a jpeg image attachment as classifiable', function (): void {
    $attachment = \makeAttachment('receipt.jpg', 'image/jpeg');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeTrue();
});

\it('classifies a png image attachment as classifiable', function (): void {
    $attachment = \makeAttachment('scan.png', 'image/png');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeTrue();
});

\it('does not classify a zip file as classifiable', function (): void {
    $attachment = \makeAttachment('archive.zip', 'application/zip');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeFalse();
});

\it('does not classify a fortrydelsesret pdf as classifiable', function (): void {
    $attachment = \makeAttachment('Fortrydelsesret.pdf', 'application/pdf');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeFalse();
});

\it('does not classify a withdrawal pdf as classifiable', function (): void {
    $attachment = \makeAttachment('Right-of-Withdrawal.pdf', 'application/pdf');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeFalse();
});

\it('does not classify a cancellation pdf as classifiable', function (): void {
    $attachment = \makeAttachment('cancellation_policy.pdf', 'application/pdf');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeFalse();
});

\it('does not classify a widerruf pdf as classifiable', function (): void {
    $attachment = \makeAttachment('Widerrufsbelehrung.pdf', 'application/pdf');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeFalse();
});

\it('does not classify an agb pdf as classifiable', function (): void {
    $attachment = \makeAttachment('AGB.pdf', 'application/pdf');

    \expect(ClassifiableMailAttachment::isClassifiable($attachment))->toBeFalse();
});

function makeSummary(): App\Services\Fastmail\DTOs\EmailSummary
{
    return new App\Services\Fastmail\DTOs\EmailSummary(
        id: 'test-id',
        subject: 'Test',
        from: [],
        receivedAt: null,
        preview: null,
        hasAttachment: true,
        mailboxIds: [],
    );
}

\it('returns null from firstFromMessage when all attachments are non-receipt documents', function (): void {
    $message = new App\Services\Fastmail\DTOs\EmailMessage(
        summary: \makeSummary(),
        from: [],
        to: [],
        textBody: 'Receipt body text',
        htmlBody: null,
        attachments: \collect([
            \makeAttachment('Fortrydelsesret.pdf', 'application/pdf', 'blob-1'),
            \makeAttachment('Fortrydelsesret.pdf', 'application/pdf', 'blob-2'),
            \makeAttachment('Fortrydelsesret.pdf', 'application/pdf', 'blob-3'),
        ]),
    );

    \expect(ClassifiableMailAttachment::firstFromMessage($message))->toBeNull();
});

\it('returns the first classifiable attachment when present alongside non-receipt documents', function (): void {
    $receiptAttachment = \makeAttachment('order-confirmation.pdf', 'application/pdf', 'blob-receipt');
    $message = new App\Services\Fastmail\DTOs\EmailMessage(
        summary: \makeSummary(),
        from: [],
        to: [],
        textBody: 'Some body',
        htmlBody: null,
        attachments: \collect([
            \makeAttachment('Fortrydelsesret.pdf', 'application/pdf', 'blob-1'),
            $receiptAttachment,
        ]),
    );

    \expect(ClassifiableMailAttachment::firstFromMessage($message))->toBe($receiptAttachment);
});
