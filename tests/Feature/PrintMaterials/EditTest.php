<?php

declare(strict_types=1);

use App\Livewire\PrintMaterials\Edit;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();
    $this->materialType = PrintMaterialType::factory()->create();
    $this->material = PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
        'name' => 'Original Material',
        'price_per_kg_dkk' => 100.00,
        'waste_factor_pct' => 5.0,
        'notes' => 'Original notes',
    ]);
});

\test('can update a print material', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Updated Material')
        ->set('price_per_kg_dkk', '200.00')
        ->set('waste_factor_pct', '10.0')
        ->set('notes', 'Updated notes')
        ->call('save')
        ->assertRedirect(\route('print-materials.index'));

    $this->assertDatabaseHas('print_materials', [
        'id' => $this->material->id,
        'name' => 'Updated Material',
        'price_per_kg_dkk' => 200.00,
        'waste_factor_pct' => 10.0,
        'notes' => 'Updated notes',
    ]);
})->covers(Edit::class);

\test('save method returns Redirector on successful update', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Updated Material')
        ->set('price_per_kg_dkk', '200.00')
        ->call('save');

    // Verify redirect was returned (not null)
    $component->assertRedirect(\route('print-materials.index'));
})->covers(Edit::class);

\test('can update material with minimal required fields', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('material_type_id', $this->materialType->id)
        ->set('name', 'Minimal Update')
        ->set('price_per_kg_dkk', '150.00')
        ->set('waste_factor_pct', '0')
        ->set('notes', null)
        ->call('save')
        ->assertRedirect(\route('print-materials.index'));

    $this->assertDatabaseHas('print_materials', [
        'id' => $this->material->id,
        'name' => 'Minimal Update',
        'waste_factor_pct' => 0,
        'notes' => null,
    ]);
})->covers(Edit::class);

\test('validates material_type_id is required', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('material_type_id', null)
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['material_type_id']);
})->covers(Edit::class);

\test('validates material_type_id exists', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('material_type_id', 99999) // Non-existent type
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['material_type_id']);
})->covers(Edit::class);

\test('validates name is required', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', '')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('validates name uniqueness within material type excluding current record', function () {
    $otherMaterial = PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
        'name' => 'Other Material',
    ]);

    // Should allow keeping the same name
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Original Material') // Same as original
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasNoErrors(['name'])
        ->assertRedirect(\route('print-materials.index'));

    // Should not allow using another material's name in same type
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Other Material')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('allows same name for different material types', function () {
    $otherType = PrintMaterialType::factory()->create();
    // Create a material in the other type with a different name
    PrintMaterial::factory()->create([
        'material_type_id' => $otherType->id,
        'name' => 'Other Material Name',
    ]);

    // Should allow using the original material's name when moving to different type
    // (as long as that name doesn't exist in the target type)
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('material_type_id', $otherType->id)
        ->set('name', 'Original Material') // Keep original name, but change type
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasNoErrors(['name'])
        ->assertRedirect(\route('print-materials.index'));
})->covers(Edit::class);

\test('validates price_per_kg_dkk is required', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '')
        ->call('save')
        ->assertHasErrors(['price_per_kg_dkk']);
})->covers(Edit::class);

\test('validates price_per_kg_dkk is numeric', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', 'not-a-number')
        ->call('save')
        ->assertHasErrors(['price_per_kg_dkk']);
})->covers(Edit::class);

\test('validates price_per_kg_dkk minimum value', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '0.001') // Below min:0.01
        ->call('save')
        ->assertHasErrors(['price_per_kg_dkk']);
})->covers(Edit::class);

\test('validates waste_factor_pct is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->set('waste_factor_pct', '')
        ->call('save')
        ->assertHasNoErrors(['waste_factor_pct'])
        ->assertRedirect(\route('print-materials.index'));
})->covers(Edit::class);

\test('validates waste_factor_pct maximum value', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->set('waste_factor_pct', '101') // Exceeds max:100
        ->call('save')
        ->assertHasErrors(['waste_factor_pct']);
})->covers(Edit::class);

\test('validates notes is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Test Material')
        ->set('price_per_kg_dkk', '150.50')
        ->set('notes', null)
        ->call('save')
        ->assertHasNoErrors(['notes'])
        ->assertRedirect(\route('print-materials.index'));
})->covers(Edit::class);

\test('validates name max length', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', \str_repeat('a', 256)) // Exceeds max:255
        ->set('price_per_kg_dkk', '150.50')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('successfully redirects after update', function () {
    // This test verifies that save() returns a Redirector (not null)
    // which prevents the "Return value must be of type Redirector, null returned" error
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Updated Material')
        ->set('price_per_kg_dkk', '200.00')
        ->call('save')
        ->assertRedirect(\route('print-materials.index'));
})->covers(Edit::class);

\test('mounts with material data', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material]);

    $this->assertEquals($this->materialType->id, $component->get('material_type_id'));
    $this->assertEquals('Original Material', $component->get('name'));
    $this->assertEquals('100', $component->get('price_per_kg_dkk'));
    $this->assertEquals('5', $component->get('waste_factor_pct'));
    $this->assertEquals('Original notes', $component->get('notes'));
})->covers(Edit::class);

\test('returns null when duplicate name exists for same material type', function () {
    $otherMaterial = PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
        'name' => 'Duplicate Name',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['material' => $this->material])
        ->set('name', 'Duplicate Name')
        ->set('price_per_kg_dkk', '150.50')
        ->call('save');

    // Should have validation error and not redirect
    $component->assertHasErrors(['name']);
    // The method returns null in this case, which is correct for ?Redirector
})->covers(Edit::class);
