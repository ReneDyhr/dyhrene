<?php

declare(strict_types=1);

use App\Services\Fastmail\FastmailEmailService;
use App\Services\Fastmail\FastmailJmapClient;
use Illuminate\Support\Facades\Cache;

\it('extracts inline image parts as attachments', function (): void {
    Cache::flush();
    \app()->forgetInstance(FastmailJmapClient::class);
    \config([
        'fastmail.token' => 'test-token',
        'fastmail.email' => 'user@fastmail.com',
        'fastmail.session_url' => 'https://api.fastmail.com/jmap/session',
    ]);

    \fakeFastmailJmapApi([
        'emailList' => [
            [
                'id' => 'email-inline-image',
                'subject' => '',
                'from' => [['name' => 'Sender', 'email' => 'sender@example.com']],
                'receivedAt' => '2026-05-21T06:10:00Z',
                'preview' => '',
                'hasAttachment' => true,
                'mailboxIds' => ['mbox-1'],
                'bodyValues' => [
                    '1' => ['value' => "\n"],
                ],
                'bodyStructure' => [
                    'type' => 'multipart/related',
                    'subParts' => [
                        [
                            'type' => 'text/plain',
                            'blobId' => 'blob-text',
                        ],
                        [
                            'type' => 'image/jpeg',
                            'disposition' => 'inline',
                            'name' => '12446.jpg',
                            'blobId' => 'blob-inline-jpeg',
                            'size' => 12000,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $service = new FastmailEmailService(new FastmailJmapClient());
    $message = $service->getMessage('email-inline-image');

    \expect($message->attachments)->toHaveCount(1);

    $attachment = $message->attachments->first();
    \expect($attachment->name)->toBe('12446.jpg')
        ->and($attachment->type)->toBe('image/jpeg')
        ->and($attachment->blobId)->toBe('blob-inline-jpeg');
});
