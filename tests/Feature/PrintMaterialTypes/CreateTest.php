<?php

declare(strict_types=1);

use App\Livewire\PrintMaterialTypes\Create;
use App\Models\PrintMaterialType;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();
});

\test('can create a print material type', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Material Type')
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertRedirect(\route('print-material-types.index'));

    $this->assertDatabaseHas('print_material_types', [
        'name' => 'Test Material Type',
        'avg_kwh_per_hour' => 0.15,
    ]);
})->covers(Create::class);

\test('save method returns Redirector on successful creation', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Material Type')
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save');

    // Verify redirect was returned (not null)
    $component->assertRedirect(\route('print-material-types.index'));
})->covers(Create::class);

\test('validates name is required', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', '')
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('validates name is unique', function () {
    PrintMaterialType::factory()->create(['name' => 'Existing Type']);

    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Existing Type')
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('validates avg_kwh_per_hour is required', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Type')
        ->set('avg_kwh_per_hour', '')
        ->call('save')
        ->assertHasErrors(['avg_kwh_per_hour']);
})->covers(Create::class);

\test('validates avg_kwh_per_hour is numeric', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Type')
        ->set('avg_kwh_per_hour', 'not-a-number')
        ->call('save')
        ->assertHasErrors(['avg_kwh_per_hour']);
})->covers(Create::class);

\test('validates avg_kwh_per_hour minimum value', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Type')
        ->set('avg_kwh_per_hour', '0.00001') // Below min:0.0001
        ->call('save')
        ->assertHasErrors(['avg_kwh_per_hour']);
})->covers(Create::class);

\test('validates name max length', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', \str_repeat('a', 256)) // Exceeds max:255
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('successfully redirects after creation', function () {
    // This test verifies that save() returns a Redirector (not null)
    // which prevents the "Return value must be of type Redirector, null returned" error
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Material Type')
        ->set('avg_kwh_per_hour', '0.15')
        ->call('save')
        ->assertRedirect(\route('print-material-types.index'));
})->covers(Create::class);
