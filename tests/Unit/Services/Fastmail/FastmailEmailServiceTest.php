<?php

declare(strict_types=1);

use App\Services\Fastmail\EmailQuery;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Fastmail\FastmailJmapClient;
use Illuminate\Support\Facades\Cache;

\beforeEach(function () {
    Cache::flush();
    \app()->forgetInstance(FastmailJmapClient::class);
    \config([
        'fastmail.token' => 'test-token',
        'fastmail.email' => 'user@fastmail.com',
        'fastmail.session_url' => 'https://api.fastmail.com/jmap/session',
    ]);

    \fakeFastmailJmapApi([
        'emailQueryIds' => ['email-1'],
        'emailList' => [
            [
                'id' => 'email-1',
                'subject' => 'Your receipt',
                'from' => [['name' => 'Shop', 'email' => 'shop@example.com']],
                'receivedAt' => '2026-05-01T10:00:00Z',
                'preview' => 'Thanks for your order',
                'hasAttachment' => true,
                'mailboxIds' => ['mbox-1'],
            ],
        ],
        'mailboxes' => [
            [
                'id' => 'mbox-1',
                'name' => 'Inbox',
                'role' => 'inbox',
                'totalEmails' => 1,
                'unreadEmails' => 0,
            ],
        ],
    ]);
});

\it('searches emails and maps summaries', function () {
    $service = new FastmailEmailService(new FastmailJmapClient());

    $search = $service->search(
        (new EmailQuery())->inMailbox('mbox-1')->limit(10),
    );

    \expect($search['result']->total)->toBe(1)
        ->and($search['result']->ids)->toBe(['email-1'])
        ->and($search['summaries'])->toHaveCount(1);

    $summary = $search['summaries']->first();
    \expect($summary->subject)->toBe('Your receipt')
        ->and($summary->fromDisplay())->toContain('shop@example.com')
        ->and($summary->hasAttachment)->toBeTrue();
});

\it('downloads and decodes blob data', function () {
    $service = new FastmailEmailService(new FastmailJmapClient());

    $bytes = $service->downloadBlob('blob-1');

    \expect($bytes)->toBe('pdf-bytes');
});
