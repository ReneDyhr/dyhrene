<?php

declare(strict_types=1);

use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Receipts\ReceiptMailBodyImageRenderer;
use Carbon\CarbonImmutable;

\it('renders email body text to jpeg bytes', function (): void {
    if (!\extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension is not available.');
    }

    $message = new EmailMessage(
        summary: new EmailSummary(
            id: 'email-1',
            subject: 'Receipt from store',
            from: [['email' => 'store@example.com']],
            receivedAt: CarbonImmutable::parse('2026-05-01T12:00:00Z'),
            preview: null,
            hasAttachment: false,
            mailboxIds: [],
        ),
        from: [],
        to: [],
        textBody: "Line one\nLine two\nTotal 50 DKK",
        htmlBody: null,
        attachments: \collect(),
    );

    $bytes = (new ReceiptMailBodyImageRenderer())->renderToJpegBytes($message);

    \expect($bytes)->not->toBe('')
        ->and(\str_starts_with($bytes, "\xFF\xD8\xFF"))->toBeTrue();
});
