<?php

declare(strict_types=1);

use App\Livewire\PrintCustomers\Create;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();
});

\test('can create a print customer', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->set('email', 'test@example.com')
        ->set('phone', '1234567890')
        ->set('notes', 'Test notes')
        ->call('save')
        ->assertRedirect(\route('print-customers.index'));

    $this->assertDatabaseHas('print_customers', [
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'notes' => 'Test notes',
    ]);
})->covers(Create::class);

\test('save method returns Redirector on successful creation', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->set('email', 'test@example.com')
        ->call('save');

    // Verify redirect was returned (not null)
    $component->assertRedirect(\route('print-customers.index'));
})->covers(Create::class);

\test('can create customer with minimal required fields', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Minimal Customer')
        ->call('save')
        ->assertRedirect(\route('print-customers.index'));

    $this->assertDatabaseHas('print_customers', [
        'name' => 'Minimal Customer',
        'email' => null,
        'phone' => null,
        'notes' => null,
    ]);
})->covers(Create::class);

\test('validates name is required', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('validates email format when provided', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->set('email', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['email']);
})->covers(Create::class);

\test('validates email is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->set('email', null)
        ->call('save')
        ->assertHasNoErrors(['email'])
        ->assertRedirect(\route('print-customers.index'));
})->covers(Create::class);

\test('validates phone is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->set('phone', null)
        ->call('save')
        ->assertHasNoErrors(['phone'])
        ->assertRedirect(\route('print-customers.index'));
})->covers(Create::class);

\test('validates notes is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->set('notes', null)
        ->call('save')
        ->assertHasNoErrors(['notes'])
        ->assertRedirect(\route('print-customers.index'));
})->covers(Create::class);

\test('validates name max length', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', \str_repeat('a', 256)) // Exceeds max:255
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Create::class);

\test('validates email max length', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->set('email', \str_repeat('a', 250) . '@example.com') // Exceeds max:255
        ->call('save')
        ->assertHasErrors(['email']);
})->covers(Create::class);

\test('successfully redirects after creation', function () {
    // This test verifies that save() returns a Redirector (not null)
    // which prevents the "Return value must be of type Redirector, null returned" error
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Customer')
        ->call('save')
        ->assertRedirect(\route('print-customers.index'));
})->covers(Create::class);
