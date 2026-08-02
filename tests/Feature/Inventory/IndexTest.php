<?php

declare(strict_types=1);

use App\Enums\InventoryStatusEnum;
use App\Livewire\Inventory\Index;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->category = InventoryCategory::factory()->create(['user_id' => $this->user->id]);
});

\covers(Index::class);

\test('it lists only the authenticated users items', function (): void {
    $otherUser = User::factory()->create();

    InventoryItem::factory()->count(3)->create(['user_id' => $this->user->id]);
    InventoryItem::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $component = Livewire::actingAs($this->user)
        ->test(Index::class);

    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 3;
    });
});

\test('it filters by category', function (): void {
    $categoryA = InventoryCategory::factory()->create(['user_id' => $this->user->id]);
    $categoryB = InventoryCategory::factory()->create(['user_id' => $this->user->id]);

    InventoryItem::factory()->count(2)->create(['user_id' => $this->user->id, 'category_id' => $categoryA->id]);
    InventoryItem::factory()->count(3)->create(['user_id' => $this->user->id, 'category_id' => $categoryB->id]);

    $component = Livewire::actingAs($this->user)
        ->test(Index::class)
        ->set('categoryFilter', $categoryA->id);

    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 2;
    });
});

\test('it filters by status', function (): void {
    InventoryItem::factory()->count(4)->create(['user_id' => $this->user->id, 'status' => InventoryStatusEnum::Owned]);
    InventoryItem::factory()->count(1)->create(['user_id' => $this->user->id, 'status' => InventoryStatusEnum::Sold]);

    $component = Livewire::actingAs($this->user)
        ->test(Index::class)
        ->set('statusFilter', InventoryStatusEnum::Sold->value);

    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 1;
    });
});

\test('it searches name brand model and serial number', function (): void {
    InventoryItem::factory()->create(['user_id' => $this->user->id, 'name' => 'Screwdriver Set', 'brand' => '', 'model' => '', 'serial_number' => '']);
    InventoryItem::factory()->create(['user_id' => $this->user->id, 'name' => 'Hammer', 'brand' => 'DeWalt', 'model' => '', 'serial_number' => '']);
    InventoryItem::factory()->create(['user_id' => $this->user->id, 'name' => 'Wrench', 'brand' => '', 'model' => 'X100', 'serial_number' => '']);
    InventoryItem::factory()->create(['user_id' => $this->user->id, 'name' => 'Drill', 'brand' => '', 'model' => '', 'serial_number' => 'SN-12345678']);

    $component = Livewire::actingAs($this->user)
        ->test(Index::class)
        ->set('search', 'screw');

    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 1;
    });

    // Search by brand
    $component->set('search', 'DeWalt');
    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 1;
    });

    // Search by model
    $component->set('search', 'X100');
    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 1;
    });

    // Search by serial
    $component->set('search', 'SN-1234');
    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 1;
    });
});

\test('it paginates', function (): void {
    InventoryItem::factory()->count(30)->create(['user_id' => $this->user->id]);

    $component = Livewire::actingAs($this->user)
        ->test(Index::class);

    $component->assertViewHas('items', function ($items): bool {
        return $items->total() === 30
            && $items->perPage() === 25
            && $items->count() === 25;
    });
});

\test('it can delete an item via soft delete', function (): void {
    $item = InventoryItem::factory()->create(['user_id' => $this->user->id]);

    $component = Livewire::actingAs($this->user)
        ->test(Index::class)
        ->call('delete', $item->id);

    $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
});
