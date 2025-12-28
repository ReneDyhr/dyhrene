<?php

declare(strict_types=1);

use App\Livewire\PrintMaterials\Create;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();
    $this->materialType = PrintMaterialType::factory()->create();
});

\test('can create a print material', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->set('waste_factor_pct', '5.0')
        ->set('notes', 'Test notes')
        ->call('save')
        ->assertRedirect(\route('print-materials.index'));

    $this->assertDatabaseHas('print_materials', [
        'material_type_id' => $this->materialType->id,
        'name' => 'Test Material',
        'price_per_kg_dkk' => 150.50,
        'waste_factor_pct' => 5.0,
        'notes' => 'Test notes',
    ]);
})->covers(Create::class);

\test('save method returns Redirector on successful creation', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save');

    // Verify redirect was returned (not null)
    $component->assertRedirect(\route('print-materials.index'));
})->covers(Create::class);

\test('can create material with minimal required fields', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Minimal Material')
        ->set('price_per_kg_dkk', '100.00')
        ->set('waste_factor_pct', '0')
        ->call('save')
        ->assertRedirect(\route('print-materials.index'));

    $this->assertDatabaseHas('print_materials', [
        'name' => 'Minimal Material',
        'waste_factor_pct' => 0,
        'notes' => null,
    ]);
})->covers(Create::class);

\test('validates material_type_id is required', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', 0)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['material_type_id']);
})->covers(Create::class);

\test('validates material_type_id exists', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', 99999) // Non-existent type
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['material_type_id']);
})->covers(Create::class);

\test('validates name is required', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', '')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('validates name uniqueness within material type', function () {
    // Create existing material with same name and type
    PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
        'name' => 'Existing Material',
    ]);

    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Existing Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('allows same name for different material types', function () {
    $otherType = PrintMaterialType::factory()->create();

    // Create material with name in one type
    PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
        'name' => 'Shared Name',
    ]);

    // Should allow same name in different type
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $otherType->id)
        ->set('name', 'Shared Name')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasNoErrors(['name'])
        ->assertRedirect(\route('print-materials.index'));
})->covers(Create::class);

\test('validates price_per_kg_dkk is required', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '')
        ->call('save')
        ->assertHasErrors(['price_per_kg_dkk']);
})->covers(Create::class);

\test('validates price_per_kg_dkk is numeric', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', 'not-a-number')
        ->call('save')
        ->assertHasErrors(['price_per_kg_dkk']);
})->covers(Create::class);

\test('validates price_per_kg_dkk minimum value', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '0.001') // Below min:0.01
        ->call('save')
        ->assertHasErrors(['price_per_kg_dkk']);
})->covers(Create::class);

\test('validates waste_factor_pct is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->set('waste_factor_pct', '')
        ->call('save')
        ->assertHasNoErrors(['waste_factor_pct'])
        ->assertRedirect(\route('print-materials.index'));
})->covers(Create::class);

\test('validates waste_factor_pct maximum value', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->set('waste_factor_pct', '101') // Exceeds max:100
        ->call('save')
        ->assertHasErrors(['waste_factor_pct']);
})->covers(Create::class);

\test('validates notes is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->set('notes', null)
        ->call('save')
        ->assertHasNoErrors(['notes'])
        ->assertRedirect(\route('print-materials.index'));
})->covers(Create::class);

\test('validates name max length', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', \str_repeat('a', 256)) // Exceeds max:255
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('successfully redirects after creation', function () {
    // This test verifies that save() returns a Redirector (not null)
    // which prevents the "Return value must be of type Redirector, null returned" error
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertRedirect(\route('print-materials.index'));
})->covers(Create::class);

\test('returns null when duplicate name exists for same material type', function () {
    // Create existing material
    PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
        'name' => 'Duplicate Name',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Duplicate Name')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save');

    // Should have validation error and not redirect
    $component->assertHasErrors(['name']);
    // The method returns null in this case, which is correct for ?Redirector
})->covers(Create::class);
