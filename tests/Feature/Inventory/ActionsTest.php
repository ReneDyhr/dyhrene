<?php

declare(strict_types=1);

use App\Actions\CreateInventoryAttachmentAction;
use App\Actions\CreateInventoryItemAction;
use App\Actions\DeleteInventoryAttachmentAction;
use App\Actions\DeleteInventoryItemAction;
use App\Actions\UpdateInventoryItemAction;
use App\Enums\InventoryOwnerEnum;
use App\Enums\InventoryStatusEnum;
use App\Models\InventoryAttachment;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

\uses()->group('feature');

\covers(CreateInventoryItemAction::class);
\covers(UpdateInventoryItemAction::class);
\covers(DeleteInventoryItemAction::class);
\covers(CreateInventoryAttachmentAction::class);
\covers(DeleteInventoryAttachmentAction::class);

\beforeEach(function (): void {
    Storage::fake('wasabi');
    Storage::disk('wasabi')->makeDirectory('inventory');
    $this->user = User::factory()->create();
});

// --- CreateInventoryItemAction ---

\test('CreateInventoryItemAction creates an item with all fields', function (): void {
    $data = [
        'name' => 'Power Drill',
        'brand' => 'DeWalt',
        'model' => 'DCD791',
        'serial_number' => 'SN-999',
        'owner' => 'shared',
        'price' => '1299.95',
        'current_value' => '900.00',
        'acquisition_type' => 'bought',
        'acquisition_date' => '2025-06-15',
        'acquired_from' => 'Hardware Store',
        'status' => 'owned',
        'status_change_date' => null,
        'status_reason' => null,
        'category_id' => null,
    ];

    $item = (new CreateInventoryItemAction())->handle($this->user, $data);

    \expect($item)->toBeInstanceOf(InventoryItem::class);
    \expect($item->name)->toBe('Power Drill');
    \expect($item->brand)->toBe('DeWalt');
    \expect($item->user_id)->toBe($this->user->id);
    \expect($item->owner)->toBe(InventoryOwnerEnum::Shared);
    \expect($item->status)->toBe(InventoryStatusEnum::Owned);
    \expect((string) $item->price)->toBe('1299.95');

    $this->assertDatabaseHas('inventory_items', [
        'id' => $item->id,
        'name' => 'Power Drill',
        'user_id' => $this->user->id,
    ]);
});

\test('CreateInventoryItemAction handles minimal data', function (): void {
    $data = [
        'name' => 'Simple Item',
        'owner' => 'rene',
        'status' => 'owned',
    ];

    $item = (new CreateInventoryItemAction())->handle($this->user, $data);

    \expect($item->name)->toBe('Simple Item');
    \expect($item->brand)->toBeNull();
    \expect($item->user_id)->toBe($this->user->id);
});

// --- UpdateInventoryItemAction ---

\test('UpdateInventoryItemAction updates an item', function (): void {
    $item = InventoryItem::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Old Name',
        'brand' => 'Old Brand',
    ]);

    (new UpdateInventoryItemAction())->handle($item, [
        'name' => 'New Name',
        'brand' => 'New Brand',
        'owner' => 'jeanette',
    ]);

    \expect($item->fresh()->name)->toBe('New Name');
    \expect($item->fresh()->brand)->toBe('New Brand');
    \expect($item->fresh()->owner)->toBe(InventoryOwnerEnum::Jeanette);

    // Unchanged fields remain
    \expect($item->fresh()->user_id)->toBe($this->user->id);
});

// --- DeleteInventoryItemAction ---

\test('DeleteInventoryItemAction soft-deletes an item', function (): void {
    $item = InventoryItem::factory()->create(['user_id' => $this->user->id]);

    (new DeleteInventoryItemAction())->handle($item);

    $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
    \expect(InventoryItem::withTrashed()->find($item->id))->not->toBeNull();
});

// --- CreateInventoryAttachmentAction ---

\test('CreateInventoryAttachmentAction stores a file on wasabi', function (): void {
    $item = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

    $attachment = (new CreateInventoryAttachmentAction())->handle($item, $file);

    \expect($attachment)->toBeInstanceOf(InventoryAttachment::class);
    \expect($attachment->file_name)->toBe('receipt.pdf');
    \expect($attachment->mime_type)->toBe('application/pdf');
    \expect($attachment->inventory_item_id)->toBe($item->id);

    Storage::disk('wasabi')->assertExists($attachment->file_path);
});

\test('CreateInventoryAttachmentAction handles image files', function (): void {
    $item = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $file = UploadedFile::fake()->image('photo.jpg');

    $attachment = (new CreateInventoryAttachmentAction())->handle($item, $file);

    \expect($attachment->file_name)->toBe('photo.jpg');
    \expect($attachment->size)->toBeGreaterThan(0);
    Storage::disk('wasabi')->assertExists($attachment->file_path);
});

// --- DeleteInventoryAttachmentAction ---

\test('DeleteInventoryAttachmentAction removes file and database row', function (): void {
    $item = InventoryItem::factory()->create(['user_id' => $this->user->id]);
    $file = UploadedFile::fake()->create('doc.pdf', 100);
    $attachment = (new CreateInventoryAttachmentAction())->handle($item, $file);

    $path = $attachment->file_path;

    (new DeleteInventoryAttachmentAction())->handle($attachment);

    $this->assertDatabaseMissing('inventory_attachments', ['id' => $attachment->id]);
    Storage::disk('wasabi')->assertMissing($path);
});
