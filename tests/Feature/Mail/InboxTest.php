<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

\beforeEach(function () {
    Cache::flush();
    \app()->forgetInstance(App\Services\Fastmail\FastmailJmapClient::class);
    \config([
        'fastmail.token' => 'test-token',
        'fastmail.email' => 'user@fastmail.com',
        'fastmail.session_url' => 'https://api.fastmail.com/jmap/session',
        'fastmail.default_mailbox_role' => 'archive',
        'n8n.classify_webhook_url' => null,
        'mail_classification.min_score' => 1,
        'mail_classification.receipt_keywords' => ['receipt'],
        'mail_classification.payslip_keywords' => ['payslip'],
    ]);
});

\it('redirects guests to login', function () {
    $this->get(\route('mail.inbox'))
        ->assertRedirect(\route('login'));
});

\it('renders archive mail for authenticated users', function () {
    $user = User::factory()->create();

    \fakeFastmailJmapApi([
        'emailQueryIds' => ['email-1'],
        'emailList' => [
            [
                'id' => 'email-1',
                'subject' => 'Test message',
                'from' => [['email' => 'sender@example.com']],
                'receivedAt' => '2026-05-01T12:00:00Z',
                'preview' => 'Hello',
                'hasAttachment' => false,
                'mailboxIds' => ['mbox-archive'],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->assertSet('archiveMailboxId', 'mbox-archive')
        ->assertCount('emails', 1)
        ->assertSee('Archive')
        ->assertSee('Test message');
});

\it('classifies new messages from metadata on load', function () {
    $user = User::factory()->create();

    \fakeFastmailJmapApi([
        'emailQueryIds' => ['email-receipt'],
        'emailList' => [
            [
                'id' => 'email-receipt',
                'subject' => 'Your receipt from Shop',
                'from' => [['email' => 'shop@example.com']],
                'receivedAt' => '2026-05-01T12:00:00Z',
                'preview' => 'Thanks for your purchase',
                'hasAttachment' => false,
                'mailboxIds' => ['mbox-archive'],
                'bodyStructure' => ['type' => 'text/plain'],
                'bodyValues' => [],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->assertSee('Receipt');

    $this->assertDatabaseHas('mail_message_classifications', [
        'fastmail_email_id' => 'email-receipt',
        'document_type' => 'receipt',
    ]);
});
