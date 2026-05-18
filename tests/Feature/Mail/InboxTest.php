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
    ]);
});

\it('redirects guests to login', function () {
    $this->get(\route('mail.inbox'))
        ->assertRedirect(\route('login'));
});

\it('renders the mail inbox for authenticated users', function () {
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
                'mailboxIds' => ['mbox-inbox'],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->assertSet('mailboxId', 'mbox-inbox')
        ->assertCount('emails', 1)
        ->assertSee('Mail')
        ->assertSee('Test message');
});

\it('applies filters via livewire', function () {
    $user = User::factory()->create();

    \fakeFastmailJmapApi([
        'emailQueryIds' => [],
        'emailList' => [],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->set('from', 'receipts@store.com')
        ->set('hasAttachment', true)
        ->call('applyFilters')
        ->assertSet('from', 'receipts@store.com')
        ->assertSet('hasAttachment', true)
        ->assertCount('emails', 0);
});
