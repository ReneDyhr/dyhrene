<?php

declare(strict_types=1);

use App\Domain\Household\HouseholdSnapshot;
use App\Models\InventoryItem;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use App\Services\Household\EloquentHouseholdSnapshotReader;

\covers(EloquentHouseholdSnapshotReader::class);
\covers(HouseholdSnapshot::class);

\it('builds an ownership-safe household snapshot', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $now = new DateTimeImmutable('2026-09-14 12:00:00', new DateTimeZone('Europe/Copenhagen'));

    $olderReceipt = Receipt::factory()->for($owner)->create(['name' => 'Older', 'currency' => 'DKK', 'date' => '2026-09-02 10:00:00']);
    ReceiptItem::factory()->for($olderReceipt)->create(['quantity' => 2, 'amount' => 10.0]);
    $latestReceipt = Receipt::factory()->for($owner)->create(['name' => 'Latest', 'vendor' => 'Store', 'currency' => 'EUR', 'date' => '2026-09-12 10:00:00']);
    ReceiptItem::factory()->for($latestReceipt)->create(['quantity' => 1, 'amount' => 12.5]);
    $priorReceipt = Receipt::factory()->for($owner)->create(['name' => 'Prior', 'currency' => 'DKK', 'date' => '2026-08-31 23:59:59']);
    ReceiptItem::factory()->for($priorReceipt)->create(['quantity' => 1, 'amount' => 99.0]);
    $otherReceipt = Receipt::factory()->for($other)->create(['name' => 'Other', 'currency' => 'DKK', 'date' => '2026-09-13 10:00:00']);
    ReceiptItem::factory()->for($otherReceipt)->create(['quantity' => 1, 'amount' => 100.0]);

    InventoryItem::factory()->for($owner)->create();
    InventoryItem::factory()->for($owner)->create();
    InventoryItem::factory()->for($other)->create();

    $snapshot = (new EloquentHouseholdSnapshotReader())->read($owner->id, $now);

    \expect($snapshot->receipts->currentMonthCount)->toBe(2)
        ->and($snapshot->receipts->latest?->name)->toBe('Latest')
        ->and($snapshot->receipts->latest?->vendor)->toBe('Store')
        ->and($snapshot->receipts->amountsByCurrency)->toBe(['DKK' => 20.0, 'EUR' => 12.5])
        ->and($snapshot->inventory->count)->toBe(2)
        ->and($snapshot->latestReceipts)->toHaveCount(3)
        ->and($snapshot->latestReceipts[0]->name)->toBe('Latest')
        ->and($snapshot->latestReceipts[0]->amount)->toBe(12.5)
        ->and($snapshot->latestReceipts[0]->itemCount)->toBe(1);
});

\it('returns an empty household snapshot for a user without data', function (): void {
    $user = User::factory()->create();

    $snapshot = (new EloquentHouseholdSnapshotReader())->read(
        $user->id,
        new DateTimeImmutable('2026-09-14 12:00:00', new DateTimeZone('Europe/Copenhagen')),
    );

    \expect($snapshot->receipts->currentMonthCount)->toBe(0)
        ->and($snapshot->receipts->latest)->toBeNull()
        ->and($snapshot->receipts->amountsByCurrency)->toBe([])
        ->and($snapshot->inventory->count)->toBe(0)
        ->and($snapshot->latestReceipts)->toHaveCount(0);
});
