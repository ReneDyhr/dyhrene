<?php

declare(strict_types=1);

use App\Livewire\PrintMaterialTypes\Edit;
use App\Models\PrintMaterialType;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();
    $this->materialType = PrintMaterialType::factory()->create([
        'name' => 'Original Type',
        'avg_kwh_per_hour' => 0.10,
    ]);
});

\test('can update a print material type', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Updated Type')
        ->set('avg_kwh_per_hour', '0.20')
        ->call('save')
        ->assertRedirect(\route('print-material-types.index'));

    $this->assertDatabaseHas('print_material_types', [
        'id' => $this->materialType->id,
        'name' => 'Updated Type',
        'avg_kwh_per_hour' => 0.20,
    ]);
})->covers(Edit::class);

\test('save method returns Redirector on successful update', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Updated Type')
        ->set('avg_kwh_per_hour', '0.20')
        ->call('save');

    // Verify redirect was returned (not null)
    $component->assertRedirect(\route('print-material-types.index'));
})->covers(Edit::class);

\test('validates name is required', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', '')
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('validates name is unique excluding current record', function () {
    $otherType = PrintMaterialType::factory()->create(['name' => 'Other Type']);

    // Should allow keeping the same name
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Original Type') // Same as original
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertHasNoErrors(['name'])
        ->assertRedirect(\route('print-material-types.index'));

    // Should not allow using another type's name
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Other Type')
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('validates avg_kwh_per_hour is required', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Test Type')
        ->set('avg_kwh_per_hour', '')
        ->call('save')
        ->assertHasErrors(['avg_kwh_per_hour']);
})->covers(Edit::class);

\test('validates avg_kwh_per_hour is numeric', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Test Type')
        ->set('avg_kwh_per_hour', 'not-a-number')
        ->call('save')
        ->assertHasErrors(['avg_kwh_per_hour']);
})->covers(Edit::class);

\test('validates avg_kwh_per_hour minimum value', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Test Type')
        ->set('avg_kwh_per_hour', '0.00001') // Below min:0.0001
        ->call('save')
        ->assertHasErrors(['avg_kwh_per_hour']);
})->covers(Edit::class);

\test('validates name max length', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', \str_repeat('a', 256)) // Exceeds max:255
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('successfully redirects after update', function () {
    // This test verifies that save() returns a Redirector (not null)
    // which prevents the "Return value must be of type Redirector, null returned" error
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType])
        ->set('name', 'Updated Type')
        ->set('avg_kwh_per_hour', '0.20')
        ->call('save')
        ->assertRedirect(\route('print-material-types.index'));
})->covers(Edit::class);

\test('mounts with material type data', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['materialType' => $this->materialType]);

    $this->assertEquals('Original Type', $component->get('name'));
    $this->assertEquals('0.1', $component->get('avg_kwh_per_hour'));
})->covers(Edit::class);

