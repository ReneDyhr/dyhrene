<?php

declare(strict_types=1);

use App\Livewire\PrintCustomers\Edit;
use App\Models\PrintCustomer;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = PrintCustomer::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'phone' => '1234567890',
        'notes' => 'Original notes',
    ]);
});

\test('can update a print customer', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->set('phone', '9876543210')
        ->set('notes', 'Updated notes')
        ->call('save')
        ->assertRedirect(\route('print-customers.index'));

    $this->assertDatabaseHas('print_customers', [
        'id' => $this->customer->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone' => '9876543210',
        'notes' => 'Updated notes',
    ]);
})->covers(Edit::class);

\test('save method returns Redirector on successful update', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Updated Name')
        ->call('save');

    // Verify redirect was returned (not null)
    $component->assertRedirect(\route('print-customers.index'));
})->covers(Edit::class);

\test('can update customer with minimal required fields', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Minimal Update')
        ->set('email', null)
        ->set('phone', null)
        ->set('notes', null)
        ->call('save')
        ->assertRedirect(\route('print-customers.index'));

    $this->assertDatabaseHas('print_customers', [
        'id' => $this->customer->id,
        'name' => 'Minimal Update',
        'email' => null,
        'phone' => null,
        'notes' => null,
    ]);
})->covers(Edit::class);

\test('validates name is required', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('validates email format when provided', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Test Customer')
        ->set('email', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['email']);
})->covers(Edit::class);

\test('validates email is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Test Customer')
        ->set('email', null)
        ->call('save')
        ->assertHasNoErrors(['email'])
        ->assertRedirect(\route('print-customers.index'));
})->covers(Edit::class);

\test('validates phone is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Test Customer')
        ->set('phone', null)
        ->call('save')
        ->assertHasNoErrors(['phone'])
        ->assertRedirect(\route('print-customers.index'));
})->covers(Edit::class);

\test('validates notes is nullable', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Test Customer')
        ->set('notes', null)
        ->call('save')
        ->assertHasNoErrors(['notes'])
        ->assertRedirect(\route('print-customers.index'));
})->covers(Edit::class);

\test('validates name max length', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', \str_repeat('a', 256)) // Exceeds max:255
        ->call('save')
        ->assertHasErrors(['name']);
})->covers(Edit::class);

\test('validates email max length', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Test Customer')
        ->set('email', \str_repeat('a', 250) . '@example.com') // Exceeds max:255
        ->call('save')
        ->assertHasErrors(['email']);
})->covers(Edit::class);

\test('successfully redirects after update', function () {
    // This test verifies that save() returns a Redirector (not null)
    // which prevents the "Return value must be of type Redirector, null returned" error
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertRedirect(\route('print-customers.index'));
})->covers(Edit::class);

\test('mounts with customer data', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Edit::class, ['customer' => $this->customer]);

    $this->assertEquals('Original Name', $component->get('name'));
    $this->assertEquals('original@example.com', $component->get('email'));
    $this->assertEquals('1234567890', $component->get('phone'));
    $this->assertEquals('Original notes', $component->get('notes'));
})->covers(Edit::class);
