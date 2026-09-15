<?php

declare(strict_types=1);

use App\Domain\Overview\InventoryOverview;
use App\Domain\Overview\LatestReceipt;
use App\Domain\Overview\LatestRecipe;
use App\Domain\Overview\ObservationOverview;
use App\Domain\Overview\OverviewSnapshot;
use App\Domain\Overview\ReceiptOverview;
use App\Domain\Overview\RecipeOverview;
use App\Domain\Overview\ShoppingListOverview;
use App\Domain\Overview\WildEdibleOverview;
use App\Enums\SpeciesStatusEnum;
use App\Models\InventoryItem;
use App\Models\Observation;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\Species;
use App\Models\User;
use App\Models\WildEdible;
use App\Services\Overview\EloquentOverviewSnapshotReader;

\covers(EloquentOverviewSnapshotReader::class);
\covers(OverviewSnapshot::class);
\covers(RecipeOverview::class);
\covers(LatestRecipe::class);
\covers(ShoppingListOverview::class);
\covers(ReceiptOverview::class);
\covers(LatestReceipt::class);
\covers(InventoryOverview::class);
\covers(ObservationOverview::class);
\covers(WildEdibleOverview::class);

\it('builds an ownership-safe overview snapshot from allowed data only', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $now = new DateTimeImmutable('2026-09-14 12:00:00', new DateTimeZone('Europe/Copenhagen'));

    Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Older owner recipe', 'created_at' => '2026-09-01 08:00:00']);
    $latestRecipe = Recipe::factory()->create(['user_id' => $owner->id, 'name' => 'Latest owner recipe', 'created_at' => '2026-09-10 08:00:00']);
    Recipe::factory()->create(['user_id' => $other->id, 'name' => 'Other recipe', 'created_at' => '2026-09-13 08:00:00']);

    ShoppingList::query()->create(['user_id' => $owner->id, 'name' => 'Milk', 'order' => 1, 'status' => 'active']);
    ShoppingList::query()->create(['user_id' => $owner->id, 'name' => 'Eggs', 'order' => 2, 'status' => 'checked']);
    ShoppingList::query()->create(['user_id' => $owner->id, 'name' => '#Dairy', 'order' => 3, 'status' => 'active']);
    ShoppingList::query()->create(['user_id' => $other->id, 'name' => 'Other owner item', 'order' => 1, 'status' => 'active']);

    $olderReceipt = Receipt::factory()->for($owner)->create([
        'name' => 'Older owner receipt',
        'currency' => 'DKK',
        'date' => '2026-09-02 10:00:00',
    ]);
    ReceiptItem::factory()->for($olderReceipt)->create(['quantity' => 2, 'amount' => 10.0]);
    $latestReceipt = Receipt::factory()->for($owner)->create([
        'name' => 'Latest owner receipt',
        'vendor' => 'Owner store',
        'currency' => 'EUR',
        'date' => '2026-09-12 10:00:00',
    ]);
    ReceiptItem::factory()->for($latestReceipt)->create(['quantity' => 1, 'amount' => 12.5]);
    $priorMonthReceipt = Receipt::factory()->for($owner)->create([
        'name' => 'Prior month receipt',
        'currency' => 'DKK',
        'date' => '2026-08-31 23:59:59',
    ]);
    ReceiptItem::factory()->for($priorMonthReceipt)->create(['quantity' => 1, 'amount' => 99.0]);
    $otherReceipt = Receipt::factory()->for($other)->create([
        'name' => 'Other owner receipt',
        'currency' => 'DKK',
        'date' => '2026-09-13 10:00:00',
    ]);
    ReceiptItem::factory()->for($otherReceipt)->create(['quantity' => 1, 'amount' => 100.0]);

    InventoryItem::factory()->for($owner)->create();
    InventoryItem::factory()->for($owner)->create();
    InventoryItem::factory()->for($other)->create();

    $visibleSpecies = Species::factory()->for($owner)->create();
    Observation::factory()->for($visibleSpecies)->for($owner)->create();
    $rejectedSpecies = Species::factory()->for($owner)->create(['status' => SpeciesStatusEnum::Rejected]);
    Observation::factory()->for($rejectedSpecies)->for($owner)->create();
    $otherSpecies = Species::factory()->for($other)->create();
    Observation::factory()->for($otherSpecies)->for($owner)->create();
    Observation::factory()->for($visibleSpecies)->for($other)->create();

    WildEdible::factory()->for($owner)->create();
    WildEdible::factory()->for($owner)->create();
    WildEdible::factory()->for($other)->create();

    $snapshot = (new EloquentOverviewSnapshotReader())->read($owner->id, $now);

    \expect($snapshot->recipes->count)->toBe(2)
        ->and($snapshot->recipes->latest?->id)->toBe($latestRecipe->id)
        ->and($snapshot->recipes->latest?->name)->toBe('Latest owner recipe')
        ->and($snapshot->recipes->latest?->createdAt->format('Y-m-d H:i:s'))->toBe('2026-09-10 08:00:00')
        ->and($snapshot->shoppingList->activeActionableCount)->toBe(1)
        ->and($snapshot->receipts->currentMonthCount)->toBe(2)
        ->and($snapshot->receipts->latest?->id)->toBe($latestReceipt->id)
        ->and($snapshot->receipts->latest?->name)->toBe('Latest owner receipt')
        ->and($snapshot->receipts->latest?->vendor)->toBe('Owner store')
        ->and($snapshot->receipts->latest?->amount)->toBe(12.5)
        ->and($snapshot->receipts->amountsByCurrency)->toBe(['DKK' => 20.0, 'EUR' => 12.5])
        ->and($snapshot->inventory->count)->toBe(2)
        ->and($snapshot->observations->count)->toBe(1)
        ->and($snapshot->wildEdibles->count)->toBe(2)
        ->and(\array_keys(\get_object_vars($snapshot)))->toBe([
            'recipes',
            'shoppingList',
            'receipts',
            'inventory',
            'observations',
            'wildEdibles',
        ])
        ->and(\array_keys(\get_object_vars($snapshot->recipes)))->toBe(['count', 'latest'])
        ->and(\array_keys(\get_object_vars($snapshot->recipes->latest)))->toBe(['id', 'name', 'createdAt'])
        ->and(\array_keys(\get_object_vars($snapshot->shoppingList)))->toBe(['activeActionableCount'])
        ->and(\array_keys(\get_object_vars($snapshot->receipts)))->toBe(['currentMonthCount', 'latest', 'amountsByCurrency'])
        ->and(\array_keys(\get_object_vars($snapshot->receipts->latest)))->toBe(['id', 'name', 'vendor', 'currency', 'date', 'amount'])
        ->and(\array_keys(\get_object_vars($snapshot->inventory)))->toBe(['count'])
        ->and(\array_keys(\get_object_vars($snapshot->observations)))->toBe(['count'])
        ->and(\array_keys(\get_object_vars($snapshot->wildEdibles)))->toBe(['count']);
});

\it('returns an empty snapshot without relying on the authenticated user', function (): void {
    $snapshot = (new EloquentOverviewSnapshotReader())->read(
        User::factory()->create()->id,
        new DateTimeImmutable('2026-09-14 12:00:00', new DateTimeZone('Europe/Copenhagen')),
    );

    \expect($snapshot->recipes->count)->toBe(0)
        ->and($snapshot->recipes->latest)->toBeNull()
        ->and($snapshot->shoppingList->activeActionableCount)->toBe(0)
        ->and($snapshot->receipts->currentMonthCount)->toBe(0)
        ->and($snapshot->receipts->latest)->toBeNull()
        ->and($snapshot->receipts->amountsByCurrency)->toBe([])
        ->and($snapshot->inventory->count)->toBe(0)
        ->and($snapshot->observations->count)->toBe(0)
        ->and($snapshot->wildEdibles->count)->toBe(0);
});
