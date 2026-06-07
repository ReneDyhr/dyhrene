<?php

declare(strict_types=1);

use App\Console\Commands\ImportMailReceiptsCommand;
use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Mail\MailReceiptImportService;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;

\covers(ImportMailReceiptsCommand::class);

function makeClassification(string $emailId, MailDocumentTypeEnum $type = MailDocumentTypeEnum::Receipt, ?int $receiptId = null): MailMessageClassification
{
    /** @var MailMessageClassification */
    return MailMessageClassification::query()->create([
        'fastmail_email_id' => $emailId,
        'document_type' => $type,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
        'receipt_id' => $receiptId,
        'processed_at' => $receiptId !== null ? \now() : null,
    ]);
}

\it('imports unprocessed receipt emails', function (): void {
    $user = User::factory()->create();

    \makeClassification('email-a');
    \makeClassification('email-b');

    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldReceive('import')
        ->with(Mockery::type(User::class), 'email-a')
        ->once()
        ->andReturn(Receipt::factory()->for($user)->create(['name' => 'Receipt A']));
    $mockService->shouldReceive('import')
        ->with(Mockery::type(User::class), 'email-b')
        ->once()
        ->andReturn(Receipt::factory()->for($user)->create(['name' => 'Receipt B']));

    $this->artisan('mail:import-receipts')
        ->assertExitCode(0)
        ->expectsOutputToContain('Created: 2, failed: 0');
});

\it('skips already-imported emails', function (): void {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();

    \makeClassification('email-done', receiptId: $receipt->id);

    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldNotReceive('import');

    $this->artisan('mail:import-receipts')
        ->assertExitCode(0)
        ->expectsOutputToContain('No unprocessed receipt emails found');
});

\it('skips non-receipt emails', function (): void {
    User::factory()->create();

    \makeClassification('email-payslip', MailDocumentTypeEnum::Payslip);
    \makeClassification('email-unknown', MailDocumentTypeEnum::Unknown);

    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldNotReceive('import');

    $this->artisan('mail:import-receipts')
        ->assertExitCode(0)
        ->expectsOutputToContain('No unprocessed receipt emails found');
});

\it('handles extraction exceptions gracefully and continues', function (): void {
    $user = User::factory()->create();

    \makeClassification('email-fail');
    \makeClassification('email-ok');

    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldReceive('import')
        ->with(Mockery::type(User::class), 'email-fail')
        ->once()
        ->andThrow(new ReceiptExtractionException('Duplicate receipt'));
    $mockService->shouldReceive('import')
        ->with(Mockery::type(User::class), 'email-ok')
        ->once()
        ->andReturn(Receipt::factory()->for($user)->create());

    $this->artisan('mail:import-receipts')
        ->assertExitCode(0)
        ->expectsOutputToContain('Created: 1, failed: 1');
});

\it('imports a single email with --id', function (): void {
    $user = User::factory()->create();

    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldReceive('import')
        ->with(Mockery::type(User::class), 'email-xyz')
        ->once()
        ->andReturn(Receipt::factory()->for($user)->create(['name' => 'My Receipt']));

    $this->artisan('mail:import-receipts', ['--id' => 'email-xyz'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Receipt created');
});

\it('warns and exits success for --id when extraction fails', function (): void {
    User::factory()->create();

    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldReceive('import')
        ->with(Mockery::type(User::class), 'email-bad')
        ->once()
        ->andThrow(new ReceiptExtractionException('Not a receipt'));

    $this->artisan('mail:import-receipts', ['--id' => 'email-bad'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Skipped');
});

\it('respects --limit and caps the number of emails processed', function (): void {
    $user = User::factory()->create();

    \makeClassification('email-1');
    \makeClassification('email-2');
    \makeClassification('email-3');

    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldReceive('import')
        ->twice()
        ->andReturn(Receipt::factory()->for($user)->create());

    $this->artisan('mail:import-receipts', ['--limit' => '2'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Created: 2, failed: 0');
});

\it('fails when user id 1 does not exist', function (): void {
    $mockService = $this->mock(MailReceiptImportService::class);
    $mockService->shouldNotReceive('import');

    $this->artisan('mail:import-receipts')
        ->assertExitCode(1)
        ->expectsOutputToContain('User with id 1 not found');
});
