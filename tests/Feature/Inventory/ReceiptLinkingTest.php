<?php

declare(strict_types=1);

use App\Livewire\Inventory\Create;
use App\Livewire\Inventory\Show as InventoryShow;
use App\Livewire\Receipts\Edit as ReceiptsEdit;
use App\Livewire\Receipts\Show as ReceiptShow;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Receipt;
use App\Models\ReceiptCategory;
use App\Models\ReceiptItem;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->receiptCategory = ReceiptCategory::factory()->create(['user_id' => $this->user->id]);
    $this->inventoryCategory = InventoryCategory::factory()->create(['user_id' => $this->user->id]);
});

\covers(ReceiptShow::class);
\covers(InventoryShow::class);
\covers(Create::class);

\test('migration adds inventory_item_id to receipt_items', function (): void {
    \expect(Schema::hasColumn('receipt_items', 'inventory_item_id'))->toBeTrue();
});

\test('receiptItem belongsTo inventoryItem', function (): void {
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => $inventoryItem->id,
    ]);

    \expect($receiptItem->inventoryItem)->not->toBeNull();
    \expect($receiptItem->inventoryItem->id)->toBe($inventoryItem->id);
});

\test('inventoryItem hasOne receiptItem through reverse relation', function (): void {
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => $inventoryItem->id,
    ]);

    $inventoryItem->refresh()->load('receiptItem');
    \expect($inventoryItem->receiptItem)->not->toBeNull();
});

\test('can link receipt item to inventory item from receipt show', function (): void {
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(ReceiptShow::class, ['receipt' => $receipt])
        ->call('linkToInventory', $receiptItem->id, $inventoryItem->id);

    $this->assertDatabaseHas('receipt_items', [
        'id' => $receiptItem->id,
        'inventory_item_id' => $inventoryItem->id,
    ]);
});

\test('can unlink receipt item from inventory item', function (): void {
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => $inventoryItem->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(ReceiptShow::class, ['receipt' => $receipt])
        ->call('unlinkFromInventory', $receiptItem->id);

    $this->assertDatabaseHas('receipt_items', [
        'id' => $receiptItem->id,
        'inventory_item_id' => null,
    ]);
});

\test('shows linked receipt on inventory show page', function (): void {
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receipt = Receipt::factory()->create([
        'user_id' => $this->user->id,
        'vendor' => 'TestMart',
    ]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => $inventoryItem->id,
        'amount' => 99.99,
    ]);

    Livewire::actingAs($this->user)
        ->test(InventoryShow::class, ['item' => $inventoryItem])
        ->assertSee('TestMart');
});

\test('auto-fills price and date from receipt on inventory create', function (): void {
    $receipt = Receipt::factory()->create([
        'user_id' => $this->user->id,
        'vendor' => 'AutoMart',
        'date' => \now()->subDays(3),
    ]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => null,
        'amount' => 150.50,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('receipt_item_id', $receiptItem->id)
        ->call('linkReceipt');

    $component->assertSet('price', '150.50');
    $component->assertSet('acquisition_date', $receipt->date->format('Y-m-d'));
    $component->assertSet('acquired_from', 'AutoMart');
});

\test('does not overwrite existing price when auto-filling', function (): void {
    $receipt = Receipt::factory()->create([
        'user_id' => $this->user->id,
        'vendor' => 'AutoMart',
        'date' => \now(),
    ]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => null,
        'amount' => 150.50,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('price', '200.00')
        ->set('receipt_item_id', $receiptItem->id)
        ->call('linkReceipt');

    $component->assertSet('price', '200.00');
});

\test('saves receipt item link when creating inventory item', function (): void {
    $receipt = Receipt::factory()->create([
        'user_id' => $this->user->id,
        'vendor' => 'LinkMart',
        'date' => \now(),
    ]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => null,
        'name' => 'Widget',
        'amount' => 50.00,
    ]);

    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Linked Widget')
        ->set('receipt_item_id', $receiptItem->id)
        ->call('save')
        ->assertRedirect();

    $this->assertDatabaseHas('receipt_items', [
        'id' => $receiptItem->id,
        'inventory_item_id' => InventoryItem::query()->latest()->first()->id,
    ]);
});

\test('only shows unlinked receipt items in dropdown', function (): void {
    $alreadyLinkedItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $linkedItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => $alreadyLinkedItem->id,
    ]);
    $unlinkedItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => null,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Create::class);

    $items = $component->get('availableReceiptItems');
    $itemIds = $items->pluck('id')->toArray();

    \expect($itemIds)->not->toContain($linkedItem->id);
    \expect($itemIds)->toContain($unlinkedItem->id);
});

\test('cross-user inventory item is not visible for linking', function (): void {
    $otherUser = User::factory()->create();
    $otherInventoryItem = InventoryItem::factory()->create(['user_id' => $otherUser->id]);
    $myInventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);

    $component = Livewire::actingAs($this->user)
        ->test(ReceiptShow::class, ['receipt' => Receipt::factory()->create(['user_id' => $this->user->id])]);

    $items = $component->get('availableItems');
    $itemIds = $items->pluck('id')->toArray();

    \expect($itemIds)->toContain($myInventoryItem->id);
    \expect($itemIds)->not->toContain($otherInventoryItem->id);
});

\covers(ReceiptsEdit::class);

\test('receipt edit preserves inventory_item_id when saving items', function (): void {
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => $inventoryItem->id,
        'name' => 'Linked Item',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ReceiptsEdit::class, ['receipt' => $receipt]);

    $items = $component->get('items');
    \expect($items[0]['inventory_item_id'])->toBe($inventoryItem->id);
});

\test('receipt edit resolveInventoryItemId rejects cross-user inventory items', function (): void {
    $otherUser = User::factory()->create();
    $otherItem = InventoryItem::factory()->create(['user_id' => $otherUser->id]);
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => null,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ReceiptsEdit::class, ['receipt' => $receipt]);

    // Set a cross-user inventory_item_id and try to save
    $component->set('itemEdits.' . $receiptItem->id . '.inventory_item_id', $otherItem->id);

    $this->expectException(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    $component->call('save');
});

\test('receipt edit save writes inventory_item_id correctly', function (): void {
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => null,
        'name' => 'Unlinked Item',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ReceiptsEdit::class, ['receipt' => $receipt]);

    // Set the link and save
    $component->set('itemEdits.' . $receiptItem->id . '.inventory_item_id', $inventoryItem->id)
        ->call('save');

    // Receipt edit delete-and-recreates items, so check by name + inventory_item_id
    $this->assertDatabaseHas('receipt_items', [
        'receipt_id' => $receipt->id,
        'name' => 'Unlinked Item',
        'inventory_item_id' => $inventoryItem->id,
    ]);
});

\test('receipt edit can unset inventory_item_id', function (): void {
    $receipt = Receipt::factory()->create(['user_id' => $this->user->id]);
    $inventoryItem = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $receiptItem = ReceiptItem::factory()->create([
        'receipt_id' => $receipt->id,
        'category_id' => $this->receiptCategory->id,
        'inventory_item_id' => $inventoryItem->id,
        'name' => 'Was Linked',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ReceiptsEdit::class, ['receipt' => $receipt]);

    // itemEdits uses database IDs as keys — use the actual receipt item ID
    $component->set('itemEdits.' . $receiptItem->id . '.inventory_item_id', null)
        ->call('save');

    // After delete-and-recreate, check by receipt + name
    $this->assertDatabaseHas('receipt_items', [
        'receipt_id' => $receipt->id,
        'name' => 'Was Linked',
        'inventory_item_id' => null,
    ]);
});
