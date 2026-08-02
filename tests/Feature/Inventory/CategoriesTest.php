<?php

declare(strict_types=1);

use App\Livewire\Inventory\Categories;
use App\Models\InventoryCategory;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\covers(Categories::class);

\beforeEach(function (): void {
    $this->user = User::factory()->create();
});

\test('it lists categories for the authenticated user', function (): void {
    InventoryCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Tools']);
    InventoryCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Electronics']);

    $otherUser = User::factory()->create();
    InventoryCategory::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other']);

    Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->assertSee('Tools')
        ->assertSee('Electronics')
        ->assertDontSee('Other');
});

\test('it can create a category', function (): void {
    Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->set('addName', 'Furniture')
        ->set('addColor', '#ff0000')
        ->call('addCategory')
        ->assertRedirect(\route('inventory.categories'));

    $this->assertDatabaseHas('inventory_categories', [
        'name' => 'Furniture',
        'color' => '#ff0000',
        'user_id' => $this->user->id,
    ]);
});

\test('it can create a category without color', function (): void {
    Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->set('addName', 'Minimal')
        ->call('addCategory')
        ->assertRedirect(\route('inventory.categories'));

    $this->assertDatabaseHas('inventory_categories', [
        'name' => 'Minimal',
        'user_id' => $this->user->id,
    ]);
});

\test('it validates category name is required', function (): void {
    Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->set('addName', '')
        ->call('addCategory')
        ->assertHasErrors(['addName' => 'required']);
});

\test('it can edit a category', function (): void {
    $category = InventoryCategory::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Old Name',
        'color' => '#000000',
    ]);

    Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->call('showEditCategory', $category->id)
        ->set('editName', 'New Name')
        ->set('editColor', '#ffffff')
        ->call('editCategory')
        ->assertRedirect(\route('inventory.categories'));

    $this->assertDatabaseHas('inventory_categories', [
        'id' => $category->id,
        'name' => 'New Name',
        'color' => '#ffffff',
    ]);
});

\test('it prevents editing another users category', function (): void {
    $otherUser = User::factory()->create();
    $category = InventoryCategory::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Others Category',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Categories::class);

    try {
        $component->call('showEditCategory', $category->id);
    } catch (Illuminate\Database\Eloquent\ModelNotFoundException) {
        // Expected — forAuthUser scope filters out other user's categories
    }

    // editId should remain 0 — category was not loaded
    \expect($component->get('editId'))->toBe(0);
});

\test('it can delete a category after confirming', function (): void {
    $category = InventoryCategory::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'To Delete',
    ]);

    Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->call('showDeleteCategory', $category->id)
        ->set('deleteCheck', true)
        ->call('deleteCategory')
        ->assertRedirect(\route('inventory.categories'));

    $this->assertDatabaseMissing('inventory_categories', ['id' => $category->id]);
});

\test('it requires confirmation to delete a category', function (): void {
    $category = InventoryCategory::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Keep Me',
    ]);

    Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->call('showDeleteCategory', $category->id)
        ->set('deleteCheck', false)
        ->call('deleteCategory')
        ->assertHasErrors(['deleteCheck' => 'accepted']);

    $this->assertDatabaseHas('inventory_categories', ['id' => $category->id]);
});

\test('it resets add fields after creating', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->set('addName', 'Test')
        ->set('addColor', '#abc')
        ->call('addCategory');

    \expect($component->get('addName'))->toBe('');
    \expect($component->get('addColor'))->toBe('');
});

\test('it resets edit fields after editing', function (): void {
    $category = InventoryCategory::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Edit Me',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Categories::class)
        ->call('showEditCategory', $category->id)
        ->set('editName', 'Updated')
        ->call('editCategory');

    \expect($component->get('editId'))->toBe(0);
    \expect($component->get('editName'))->toBe('');
});
