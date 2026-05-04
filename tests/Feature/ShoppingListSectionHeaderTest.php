<?php

declare(strict_types=1);

use App\Livewire\Shopping\ShoppingList;
use App\Models\ShoppingList as ShoppingListRow;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\test('shopping list check ignores section header row', function (): void {
    $user = User::factory()->create();
    $row = new ShoppingListRow();
    $row->user_id = $user->id;
    $row->name = '#Dairy';
    $row->order = 1;
    $row->status = 'active';
    $row->save();

    Livewire::actingAs($user)
        ->test(ShoppingList::class)
        ->call('check', $row->id);

    expect($row->fresh()->status)->toBe('active');
});

\test('shopping list uncheck ignores section header row', function (): void {
    $user = User::factory()->create();
    $row = new ShoppingListRow();
    $row->user_id = $user->id;
    $row->name = '#Dairy';
    $row->order = 1;
    $row->status = 'checked';
    $row->save();

    Livewire::actingAs($user)
        ->test(ShoppingList::class)
        ->call('uncheck', $row->id);

    expect($row->fresh()->status)->toBe('checked');
});
