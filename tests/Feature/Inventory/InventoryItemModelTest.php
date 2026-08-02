<?php

declare(strict_types=1);

use App\Enums\InventoryAcquisitionTypeEnum;
use App\Enums\InventoryOwnerEnum;
use App\Enums\InventoryStatusEnum;
use App\Models\InventoryAttachment;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;

\covers(InventoryItem::class);

\it('casts owner, acquisition_type and status to enums', function (): void {
    $item = InventoryItem::factory()->create([
        'owner' => 'shared',
        'acquisition_type' => 'bought',
        'status' => 'owned',
    ]);

    \expect($item->owner)->toBeInstanceOf(InventoryOwnerEnum::class)
        ->and($item->acquisition_type)->toBeInstanceOf(InventoryAcquisitionTypeEnum::class)
        ->and($item->status)->toBeInstanceOf(InventoryStatusEnum::class);
});

\it('scopes items to the authenticated user', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $itemA = InventoryItem::factory()->for($userA)->create();
    $itemB = InventoryItem::factory()->for($userB)->create();

    $this->actingAs($userA);

    $items = InventoryItem::query()->forAuthUser()->get();

    \expect($items)->toHaveCount(1)
        ->and($items->first()->id)->toBe($itemA->id);
});

\it('soft-deletes items', function (): void {
    $item = InventoryItem::factory()->create();

    $item->delete();

    $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
});

\it('belongs to a category and has attachments', function (): void {
    $category = InventoryCategory::factory()->create();
    $item = InventoryItem::factory()->create(['category_id' => $category->id]);

    InventoryAttachment::factory()->count(2)->create(['inventory_item_id' => $item->id]);

    \expect($item->category)->not->toBeNull()
        ->and($item->category->id)->toBe($category->id)
        ->and($item->attachments)->toHaveCount(2);
});
