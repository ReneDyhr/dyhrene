<?php

declare(strict_types=1);

use App\Livewire\Inventory\Create;
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
});

\covers(Create::class);

\test('it can create an inventory item', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Drill')
        ->set('brand', 'Bosch')
        ->set('model', 'GBH 2-28')
        ->set('serial_number', 'SN-123456')
        ->set('owner', 'rene')
        ->set('price', '299.95')
        ->set('current_value', '200.00')
        ->set('acquisition_type', 'bought')
        ->set('acquisition_date', '2024-01-15')
        ->set('acquired_from', 'Hardware Store')
        ->set('status', 'owned')
        ->call('save')
        ->assertRedirect(\route('inventory.show', InventoryItem::latest()->first()));

    $this->assertDatabaseHas('inventory_items', [
        'name' => 'Test Drill',
        'brand' => 'Bosch',
        'model' => 'GBH 2-28',
        'serial_number' => 'SN-123456',
        'user_id' => $this->user->id,
    ]);
});

\test('it requires a name', function (): void {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

\test('it can create an item with a file attachment', function (): void {
    $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Item')
        ->set('upload', $file)
        ->call('save')
        ->assertRedirect(\route('inventory.show', InventoryItem::latest()->first()));

    $item = InventoryItem::latest()->first();
    \expect($item->attachments)->toHaveCount(1);
    \expect($item->attachments->first()->file_name)->toBe('receipt.pdf');

    Storage::disk('wasabi')->assertExists($item->attachments->first()->file_path);
});
