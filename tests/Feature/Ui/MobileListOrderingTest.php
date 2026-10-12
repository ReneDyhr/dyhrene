<?php

declare(strict_types=1);

use App\Livewire\Shopping\ShoppingList as ShoppingListComponent;
use App\Livewire\Storage as StorageComponent;
use App\Models\ShoppingList as ShoppingListItem;
use App\Models\Storage as StorageModel;
use App\Models\StorageItem;
use App\Models\User;
use Livewire\Livewire;

\covers(ShoppingListComponent::class);
\covers(StorageComponent::class);

\it('moves shopping rows without changing their checked state and ignores edges', function (): void {
    $user = User::factory()->create();
    $one = ShoppingListItem::query()->create(['user_id' => $user->id, 'name' => 'One', 'order' => 1, 'status' => 'active']);
    $two = ShoppingListItem::query()->create(['user_id' => $user->id, 'name' => 'Two', 'order' => 2, 'status' => 'active']);

    $component = Livewire::actingAs($user)->test(ShoppingListComponent::class)
        ->call('moveItemUp', $two->id);

    \expect($two->fresh()->order)->toBe(1)
        ->and($one->fresh()->order)->toBe(2)
        ->and($two->fresh()->status)->toBe('active');

    $component->call('moveItemUp', $two->id);
    \expect($two->fresh()->order)->toBe(1);
});

\it('moves storage rows within their storage and preserves boundary order', function (): void {
    $user = User::factory()->create();
    $storage = StorageModel::query()->create(['name' => 'Pantry']);
    $one = StorageItem::query()->create(['storage_id' => $storage->id, 'name' => 'One', 'quantity' => 1, 'sort_order' => 0]);
    $two = StorageItem::query()->create(['storage_id' => $storage->id, 'name' => 'Two', 'quantity' => 1, 'sort_order' => 1]);

    $component = Livewire::actingAs($user)->test(StorageComponent::class)
        ->call('moveItemUp', $storage->id, $two->id);

    \expect($two->fresh()->sort_order)->toBe(0)
        ->and($one->fresh()->sort_order)->toBe(1);

    $component->call('moveItemUp', $storage->id, $two->id);
    \expect($two->fresh()->sort_order)->toBe(0);
});

\it('orders only the signed-in user active shopping items', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $first = ShoppingListItem::query()->create(['user_id' => $user->id, 'name' => 'First item', 'order' => 1, 'status' => 'active']);
    $second = ShoppingListItem::query()->create(['user_id' => $user->id, 'name' => 'Second item', 'order' => 2, 'status' => 'active']);
    $other = ShoppingListItem::query()->create(['user_id' => $otherUser->id, 'name' => 'Other item', 'order' => 1, 'status' => 'active']);

    Livewire::actingAs($user)->test(ShoppingListComponent::class)->call('updateOrder', [$second->id, 999999, $other->id, $first->id]);

    \expect($second->fresh()->order)->toBe(1)
        ->and($first->fresh()->order)->toBe(2)
        ->and($other->fresh()->order)->toBe(1);
});

\it('accepts numeric string IDs emitted by sortable while preserving complete storage ordering', function (): void {
    $user = User::factory()->create();
    $storage = StorageModel::query()->create(['name' => 'Pantry']);
    $one = StorageItem::query()->create(['storage_id' => $storage->id, 'name' => 'One', 'quantity' => 1, 'sort_order' => 1]);
    $two = StorageItem::query()->create(['storage_id' => $storage->id, 'name' => 'Two', 'quantity' => 1, 'sort_order' => 2]);

    Livewire::actingAs($user)->test(StorageComponent::class)
        ->call('updateOrder', $storage->id, [(string) $two->id]);

    \expect($two->fresh()->sort_order)->toBe(0)
        ->and($one->fresh()->sort_order)->toBe(1);
});

\it('does not move shopping rows across section headers', function (): void {
    $user = User::factory()->create();
    $header = ShoppingListItem::query()->create(['user_id' => $user->id, 'name' => '#Fruit', 'order' => 1, 'status' => 'active']);
    $item = ShoppingListItem::query()->create(['user_id' => $user->id, 'name' => 'Apple', 'order' => 2, 'status' => 'active']);

    Livewire::actingAs($user)->test(ShoppingListComponent::class)
        ->call('moveItemUp', $item->id);

    \expect($header->fresh()->order)->toBe(1)
        ->and($item->fresh()->order)->toBe(2);
});

\it('associates storage edit labels with their inputs', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(StorageComponent::class)
        ->assertSee('<label for="edit-item-quantity">Antal</label>', false)
        ->assertSee('<input id="edit-item-quantity" type="number"', false)
        ->assertSee('<label for="edit-item-name">Navn</label>', false)
        ->assertSee('<input id="edit-item-name" type="text"', false);
});

\it('provides non-bubbling Danish ordering controls for shopping rows', function (): void {
    $user = User::factory()->create();
    $item = ShoppingListItem::query()->create(['user_id' => $user->id, 'name' => 'Apple', 'order' => 1, 'status' => 'active']);

    Livewire::actingAs($user)->test(ShoppingListComponent::class)
        ->assertSee('Flyt op')
        ->assertSee('Flyt ned')
        ->assertSee('wire:click.stop="moveItemUp(' . $item->id . ')"', false);
});

\it('keeps storage ordering within the selected storage and provides ordering controls', function (): void {
    $user = User::factory()->create();
    $storage = StorageModel::query()->create(['name' => 'Pantry']);
    $otherStorage = StorageModel::query()->create(['name' => 'Shelf']);
    $one = StorageItem::query()->create(['storage_id' => $storage->id, 'name' => 'One', 'quantity' => 1, 'sort_order' => 1]);
    $two = StorageItem::query()->create(['storage_id' => $storage->id, 'name' => 'Two', 'quantity' => 1, 'sort_order' => 2]);
    $foreign = StorageItem::query()->create(['storage_id' => $otherStorage->id, 'name' => 'Foreign', 'quantity' => 1, 'sort_order' => 7]);

    Livewire::actingAs($user)->test(StorageComponent::class)
        ->call('updateOrder', $storage->id, [$two->id, $foreign->id, $one->id])
        ->assertSee('Flyt op')
        ->assertSee('Flyt ned')
        ->assertSee('wire:click.stop="moveItemUp(' . $storage->id . ', ' . $one->id . ')"', false);

    \expect($two->fresh()->sort_order)->toBe(0)
        ->and($one->fresh()->sort_order)->toBe(1)
        ->and($foreign->fresh()->sort_order)->toBe(7);
});
