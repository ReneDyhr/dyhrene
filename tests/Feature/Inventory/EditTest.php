<?php

declare(strict_types=1);

use App\Livewire\Inventory\Edit;
use App\Models\InventoryAttachment;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function (): void {
    Storage::fake('wasabi');
    Storage::disk('wasabi')->makeDirectory('inventory');
    $this->user = User::factory()->create();
    $this->category = InventoryCategory::factory()->create(['user_id' => $this->user->id]);
    $this->item = InventoryItem::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
        'name' => 'Original Name',
        'brand' => 'Original Brand',
    ]);
});

\covers(Edit::class);

\test('it can edit an inventory item', function (): void {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['item' => $this->item])
        ->set('name', 'Updated Name')
        ->set('brand', 'Updated Brand')
        ->call('save')
        ->assertRedirect(\route('inventory.show', $this->item));

    $this->assertDatabaseHas('inventory_items', [
        'id' => $this->item->id,
        'name' => 'Updated Name',
        'brand' => 'Updated Brand',
    ]);
});

\test('it denies access to another users item for editing', function (): void {
    $otherUser = User::factory()->create();
    $otherItem = InventoryItem::factory()->create(['user_id' => $otherUser->id]);

    Livewire::actingAs($this->user)
        ->test(Edit::class, ['item' => $otherItem])
        ->assertForbidden();
});

\test('it can remove an attachment', function (): void {
    $attachment = InventoryAttachment::factory()->create([
        'inventory_item_id' => $this->item->id,
        'file_path' => 'inventory/test-file.pdf',
    ]);

    Storage::disk('wasabi')->put('inventory/test-file.pdf', 'test content');

    Livewire::actingAs($this->user)
        ->test(Edit::class, ['item' => $this->item])
        ->call('removeAttachment', $attachment->id);

    $this->assertDatabaseMissing('inventory_attachments', ['id' => $attachment->id]);
    Storage::disk('wasabi')->assertMissing('inventory/test-file.pdf');
});

\test('it can add a new attachment when editing', function (): void {
    $file = UploadedFile::fake()->create('new-photo.jpg', 200, 'image/jpeg');

    Livewire::actingAs($this->user)
        ->test(Edit::class, ['item' => $this->item])
        ->set('upload', $file)
        ->call('save')
        ->assertRedirect(\route('inventory.show', $this->item));

    \expect($this->item->refresh()->attachments)->toHaveCount(1);
    \expect($this->item->attachments->first()->file_name)->toBe('new-photo.jpg');
});
