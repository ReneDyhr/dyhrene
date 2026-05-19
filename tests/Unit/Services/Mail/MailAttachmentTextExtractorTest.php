<?php

declare(strict_types=1);

use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Mail\MailAttachmentTextExtractor;

\it('strips html and decodes entities from html attachments', function (): void {
    $extractor = new MailAttachmentTextExtractor();
    $html = '<!DOCTYPE html><html><head><title>Bekr&#230;ftelse</title></head><body>'
        . '<p>Udbetaling af feriepenge til din konto</p></body></html>';

    $attachment = new EmailAttachment(
        partId: '1',
        blobId: 'blob-1',
        name: 'notice.html',
        type: 'text/html',
        size: \strlen($html),
    );

    $text = $extractor->extractText($attachment, $html);

    \expect($text)->toContain('feriepenge')
        ->and($text)->toContain('Bekræftelse')
        ->not->toContain('<p>');
});
