<?php

declare(strict_types=1);

use App\Models\InventoryAttachment;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;

\uses()->group('feature');

\covers(InventoryCategory::class);

\test('InventoryCategory belongs to a user', function (): void {
    $user = User::factory()->create();
    $category = InventoryCategory::factory()->create(['user_id' => $user->id]);

    \expect($category->user)->toBeInstanceOf(User::class);
    \expect($category->user->id)->toBe($user->id);
});

\test('InventoryCategory has many items', function (): void {
    $category = InventoryCategory::factory()->create();
    InventoryItem::factory()->count(3)->create(['category_id' => $category->id]);

    \expect($category->items)->toHaveCount(3);
    \expect($category->items->first())->toBeInstanceOf(InventoryItem::class);
});

\test('InventoryCategory scopeForAuthUser filters by authenticated user', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $catA = InventoryCategory::factory()->create(['user_id' => $userA->id, 'name' => 'User A Cat']);
    InventoryCategory::factory()->create(['user_id' => $userB->id, 'name' => 'User B Cat']);

    $this->actingAs($userA);

    $categories = InventoryCategory::query()->forAuthUser()->get();

    \expect($categories)->toHaveCount(1);
    \expect($categories->first()->id)->toBe($catA->id);
});

\test('InventoryCategory fillable fields are correct', function (): void {
    $user = User::factory()->create();
    $category = InventoryCategory::factory()->create([
        'name' => 'Test',
        'color' => '#ff0000',
        'user_id' => $user->id,
    ]);

    \expect($category->name)->toBe('Test');
    \expect($category->color)->toBe('#ff0000');
});

\covers(InventoryAttachment::class);

\test('InventoryAttachment belongs to an inventory item', function (): void {
    $item = InventoryItem::factory()->create();
    $attachment = InventoryAttachment::factory()->create([
        'inventory_item_id' => $item->id,
    ]);

    \expect($attachment->item)->toBeInstanceOf(InventoryItem::class);
    \expect($attachment->item->id)->toBe($item->id);
});

\test('InventoryAttachment casts size to integer', function (): void {
    $attachment = InventoryAttachment::factory()->create([
        'inventory_item_id' => InventoryItem::factory()->create()->id,
        'size' => 2048,
    ]);

    \expect($attachment->size)->toBeInt();
    \expect($attachment->size)->toBe(2048);
});

\test('InventoryAttachment fillable fields are correct', function (): void {
    $item = InventoryItem::factory()->create();

    $attachment = InventoryAttachment::factory()->create([
        'inventory_item_id' => $item->id,
        'file_path' => 'inventory/test.pdf',
        'file_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    \expect($attachment->file_path)->toBe('inventory/test.pdf');
    \expect($attachment->file_name)->toBe('test.pdf');
    \expect($attachment->mime_type)->toBe('application/pdf');
    \expect($attachment->size)->toBe(1024);
});
