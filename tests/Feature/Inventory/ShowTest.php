<?php

declare(strict_types=1);

use App\Livewire\Inventory\Show;
use App\Models\InventoryAttachment;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->category = InventoryCategory::factory()->create(['user_id' => $this->user->id]);
});

\covers(Show::class);

\test('it can view an item', function (): void {
    $item = InventoryItem::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Drill',
        'brand' => 'Bosch',
        'category_id' => $this->category->id,
    ]);

    InventoryAttachment::factory()->count(2)->create(['inventory_item_id' => $item->id]);

    $component = Livewire::actingAs($this->user)
        ->test(Show::class, ['item' => $item]);

    $component->assertSee('Test Drill')
        ->assertSee('Bosch')
        ->assertSee($this->category->name);
});

\test('it denies access to another users item', function (): void {
    $otherUser = User::factory()->create();
    $item = InventoryItem::factory()->create(['user_id' => $otherUser->id]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['item' => $item])
        ->assertForbidden();
});
