<?php

declare(strict_types=1);

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Models\Receipt;
use App\Models\ReceiptCategory;
use App\Models\ReceiptItem;
use App\Models\User;
use App\Services\Mail\MailReceiptImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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

\it('loads more messages without clearing the list', function () {
    $user = User::factory()->create();
    $emailQueryIds = \array_map(
        static fn(int $index): string => 'email-' . $index,
        \range(1, 30),
    );
    $emailList = \array_map(
        static fn(string $id): array => [
            'id' => $id,
            'subject' => 'Message ' . $id,
            'from' => [['email' => 'sender@example.com']],
            'receivedAt' => '2026-05-01T12:00:00Z',
            'preview' => 'Hello',
            'hasAttachment' => false,
            'mailboxIds' => ['mbox-archive'],
        ],
        $emailQueryIds,
    );

    \fakeFastmailJmapApi([
        'emailQueryIds' => $emailQueryIds,
        'emailList' => $emailList,
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->assertCount('emails', 25)
        ->assertSet('total', 30)
        ->assertSet('hasMore', true)
        ->call('loadMore')
        ->assertCount('emails', 30)
        ->assertSet('hasMore', false)
        ->assertSee('Message email-1')
        ->assertSee('Message email-30');
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
        ->assertSee('Receipt')
        ->assertSee('Not created');

    $this->assertDatabaseHas('mail_message_classifications', [
        'fastmail_email_id' => 'email-receipt',
        'document_type' => 'receipt',
    ]);
});

\it('shows created receipt status in the mail list', function (): void {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();

    MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-imported',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
        'receipt_id' => $receipt->id,
        'processed_at' => \now(),
    ]);

    \fakeFastmailJmapApi([
        'emailQueryIds' => ['email-imported'],
        'emailList' => [
            [
                'id' => 'email-imported',
                'subject' => 'Your receipt from Shop',
                'from' => [['email' => 'shop@example.com']],
                'receivedAt' => '2026-05-01T12:00:00Z',
                'preview' => 'Thanks',
                'hasAttachment' => false,
                'mailboxIds' => ['mbox-archive'],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->assertSee(\route('receipts.show', ['receipt' => $receipt->id]), false)
        ->assertSet('emails.0.receiptImportStatus', 'created');
});

\it('imports a receipt from mail with attachment', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);

    MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-import',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
    ]);

    \config(['n8n.webhook_url' => 'https://n8n.example.com/webhook/receipt']);

    \fakeFastmailJmapApi([
        'n8nOutput' => \defaultN8nReceiptOutput(),
        'emailQueryIds' => ['email-import'],
        'emailList' => [
            [
                'id' => 'email-import',
                'subject' => 'Your receipt',
                'from' => [['name' => 'Shop', 'email' => 'shop@example.com']],
                'receivedAt' => '2026-05-01T12:00:00Z',
                'preview' => 'Thanks',
                'hasAttachment' => true,
                'mailboxIds' => ['mbox-archive'],
                'bodyStructure' => [
                    'type' => 'multipart/mixed',
                    'subParts' => [
                        [
                            'type' => 'image/jpeg',
                            'disposition' => 'attachment',
                            'blobId' => 'blob-1',
                            'name' => 'receipt.jpg',
                            'size' => 1024,
                        ],
                    ],
                ],
                'bodyValues' => [],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->call('processReceipt', 'email-import')
        ->assertSee('Receipt created');

    $classification = MailMessageClassification::query()
        ->where('fastmail_email_id', 'email-import')
        ->first();

    \expect($classification)->not->toBeNull()
        ->and($classification->receipt_id)->not->toBeNull();

    $receipt = Receipt::query()->find($classification->receipt_id);
    \expect($receipt)->not->toBeNull()
        ->and($receipt->vendor)->toBe('Coffee Shop')
        ->and($receipt->file_path)->not->toBeNull()
        ->and($receipt->items)->toHaveCount(1);
});

\it('imports a receipt from mail body without attachment', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);

    MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-body',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
    ]);

    \config(['n8n.webhook_url' => 'https://n8n.example.com/webhook/receipt']);

    \fakeFastmailJmapApi([
        'n8nOutput' => \defaultN8nReceiptOutput(),
        'emailQueryIds' => ['email-body'],
        'emailList' => [
            [
                'id' => 'email-body',
                'subject' => 'Order confirmation',
                'from' => [['email' => 'store@example.com']],
                'receivedAt' => '2026-05-02T10:00:00Z',
                'preview' => 'Total 50 DKK',
                'hasAttachment' => false,
                'mailboxIds' => ['mbox-archive'],
                'bodyStructure' => ['type' => 'text/plain'],
                'bodyValues' => [
                    '1' => ['value' => 'Line one\nLine two\nTotal 50 DKK'],
                ],
            ],
        ],
    ]);

    $receipt = \app(MailReceiptImportService::class)->import($user, 'email-body');

    \expect($receipt->description)->toContain('Imported from email')
        ->and($receipt->description)->not->toContain('Total 50 DKK')
        ->and($receipt->file_path)->not->toBeNull();
});

\it('does not import twice for the same email', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);
    $existing = Receipt::factory()->for($user)->create();

    MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-done',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
        'receipt_id' => $existing->id,
        'processed_at' => \now(),
    ]);

    \fakeFastmailJmapApi([
        'emailQueryIds' => [],
        'emailList' => [],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->call('processReceipt', 'email-done')
        ->assertSee('already been imported');

    \expect(Receipt::query()->count())->toBe(1);
});

\it('does not create receipt when n8n extraction fails', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);

    MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-fail',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
    ]);

    \config(['n8n.webhook_url' => 'https://n8n.example.com/webhook/receipt']);

    \fakeFastmailJmapApi([
        'n8nOutput' => \defaultN8nReceiptOutput(),
        'n8nStatus' => 500,
        'emailQueryIds' => ['email-fail'],
        'emailList' => [
            [
                'id' => 'email-fail',
                'subject' => 'Receipt',
                'from' => [['email' => 'shop@example.com']],
                'receivedAt' => '2026-05-01T12:00:00Z',
                'preview' => 'Body',
                'hasAttachment' => false,
                'mailboxIds' => ['mbox-archive'],
                'bodyStructure' => ['type' => 'text/plain'],
                'bodyValues' => [
                    '1' => ['value' => 'Receipt body text'],
                ],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->call('processReceipt', 'email-fail')
        ->assertSee('Failed to extract receipt data from webhook');

    \expect(Receipt::query()->count())->toBe(0);
});

\it('blocks duplicate receipt import from mail', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    $category = ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);

    $existing = Receipt::factory()->for($user)->create([
        'vendor' => 'Coffee Shop',
        'date' => '2026-05-01 12:00:00',
    ]);
    ReceiptItem::factory()->for($existing)->create([
        'category_id' => $category->id,
        'name' => 'Latte',
        'quantity' => 1,
        'amount' => 50.0,
    ]);

    MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-dup',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
    ]);

    \config(['n8n.webhook_url' => 'https://n8n.example.com/webhook/receipt']);

    \fakeFastmailJmapApi([
        'n8nOutput' => \defaultN8nReceiptOutput(),
        'emailQueryIds' => ['email-dup'],
        'emailList' => [
            [
                'id' => 'email-dup',
                'subject' => 'Receipt',
                'from' => [['email' => 'shop@example.com']],
                'receivedAt' => '2026-05-01T12:00:00Z',
                'preview' => 'Body',
                'hasAttachment' => false,
                'mailboxIds' => ['mbox-archive'],
                'bodyStructure' => ['type' => 'text/plain'],
                'bodyValues' => [
                    '1' => ['value' => 'Duplicate receipt body'],
                ],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(App\Livewire\Mail\Inbox::class)
        ->call('processReceipt', 'email-dup')
        ->assertSee('already been uploaded');

    \expect(Receipt::query()->count())->toBe(1);
});
