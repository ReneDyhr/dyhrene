<?php

declare(strict_types=1);

use App\Actions\DeleteReceiptAction;
use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Models\Receipt;
use App\Models\User;

\covers(DeleteReceiptAction::class);

\it('soft-deletes the receipt', function (): void {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();

    (new DeleteReceiptAction())->handle($receipt);

    $this->assertSoftDeleted('receipts', ['id' => $receipt->id]);
});

\it('clears the mail classification link so the email can be re-imported', function (): void {
    $user = User::factory()->create();
    $receipt = Receipt::factory()->for($user)->create();

    $classification = MailMessageClassification::query()->create([
        'fastmail_email_id' => 'test-email-id',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
        'receipt_id' => $receipt->id,
        'processed_at' => \now(),
    ]);

    (new DeleteReceiptAction())->handle($receipt);

    $classification->refresh();
    \expect($classification->receipt_id)->toBeNull()
        ->and($classification->processed_at)->toBeNull();
});

\it('does not affect other classifications when deleting a receipt', function (): void {
    $user = User::factory()->create();
    $receiptA = Receipt::factory()->for($user)->create();
    $receiptB = Receipt::factory()->for($user)->create();

    $classA = MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-a',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
        'receipt_id' => $receiptA->id,
        'processed_at' => \now(),
    ]);

    $classB = MailMessageClassification::query()->create([
        'fastmail_email_id' => 'email-b',
        'document_type' => MailDocumentTypeEnum::Receipt,
        'confidence' => 1.0,
        'source' => MailClassificationSourceEnum::Manual,
        'classified_at' => \now(),
        'receipt_id' => $receiptB->id,
        'processed_at' => \now(),
    ]);

    (new DeleteReceiptAction())->handle($receiptA);

    $classA->refresh();
    $classB->refresh();

    \expect($classA->receipt_id)->toBeNull()
        ->and($classA->processed_at)->toBeNull()
        ->and($classB->receipt_id)->toBe($receiptB->id)
        ->and($classB->processed_at)->not->toBeNull();
});
