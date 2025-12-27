<?php

declare(strict_types=1);

use App\Livewire\PrintJobs\Create;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\PrintOrderSequence;
use App\Models\PrintSetting;
use App\Models\User;
use Livewire\Livewire;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required test data
    $this->customer = PrintCustomer::factory()->create();
    $this->materialType = PrintMaterialType::factory()->create();
    $this->material = PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
    ]);
    PrintSetting::factory()->create(['id' => 1]);
});

\test('can create a print job', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test Print Job')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate_hours', 2)
        ->set('hours_per_plate_minutes', 30)
        ->set('labor_hours', 1.0)
        ->set('is_first_time_order', false)
        ->call('save')
        ->assertRedirect(\route('print-jobs.show', PrintJob::latest()->first()));

    $this->assertDatabaseHas('print_jobs', [
        'description' => 'Test Print Job',
        'status' => 'draft',
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
        'pieces_per_plate' => 10,
        'plates' => 2,
        'grams_per_plate' => 100.0,
        'hours_per_plate' => 2.5,
        'labor_hours' => 1.0,
        'is_first_time_order' => 0, // Database stores as 0/1, not boolean
        'calc_snapshot' => null,
    ]);
})->coversNothing();

\test('generates order number on creation', function () {
    $year = (int) \now()->year;

    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test Print Job')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate_hours', 2)
        ->set('hours_per_plate_minutes', 30)
        ->set('labor_hours', 1.0)
        ->call('save');

    $printJob = PrintJob::latest()->first();
    \expect($printJob->order_no)->toMatch('/^' . $year . '-\d{4}$/');
})->coversNothing();

\test('generates sequential order numbers', function () {
    $year = (int) \now()->year;

    // Create multiple jobs
    $orderNumbers = [];

    for ($i = 0; $i < 5; $i++) {
        Livewire::actingAs($this->user)->test(Create::class)
            ->set('date', '2025-01-15')
            ->set('description', "Job {$i}")
            ->set('customer_id', $this->customer->id)
            ->set('material_id', $this->material->id)
            ->set('pieces_per_plate', 10)
            ->set('plates', 2)
            ->set('grams_per_plate', 100)
            ->set('hours_per_plate_hours', 2)
            ->set('hours_per_plate_minutes', 30)
            ->set('labor_hours', 1.0)
            ->call('save');
    }

    // Get all created jobs
    $jobs = PrintJob::where('description', 'like', 'Job %')
        ->orderBy('id')
        ->get();

    foreach ($jobs as $job) {
        $orderNumbers[] = $job->order_no;
    }

    // Extract sequence numbers
    $sequences = [];

    foreach ($orderNumbers as $orderNo) {
        \preg_match('/' . $year . '-(\d{4})/', $orderNo, $matches);
        $sequences[] = (int) $matches[1];
    }

    // Verify sequences are sequential
    for ($i = 1; $i < \count($sequences); $i++) {
        \expect($sequences[$i])->toBe($sequences[$i - 1] + 1);
    }
})->coversNothing();

\test('validates required fields', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '') // Clear the default date
        ->set('description', '')
        ->set('customer_id', null)
        ->set('material_id', null)
        ->set('pieces_per_plate', 0) // Set to invalid value to trigger validation
        ->set('plates', 0)
        ->set('grams_per_plate', 0)
        ->set('hours_per_plate_hours', 0)
        ->set('hours_per_plate_minutes', 0)
        ->set('labor_hours', 0)
        ->call('save');

    // Check that validation errors exist
    $component->assertHasErrors([
        'description',
        'customer_id',
        'material_id',
        'pieces_per_plate',
        'plates',
    ]);
})->coversNothing();

\test('validates field constraints', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 0) // Invalid: min is 1
        ->set('plates', 11) // Invalid: max is 10
        ->set('grams_per_plate', 1000) // Invalid: max is 999
        ->set('hours_per_plate_hours', 1000) // Invalid: max is 999
        ->set('hours_per_plate_minutes', 60) // Invalid: max is 59
        ->set('labor_hours', 1000) // Invalid: max is 999
        ->call('save')
        ->assertHasErrors([
            'pieces_per_plate',
            'plates',
            'grams_per_plate',
            'hours_per_plate_hours',
            'hours_per_plate_minutes',
            'labor_hours',
        ]);
})->coversNothing();

\test('validates customer exists', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test')
        ->set('customer_id', 99999) // Non-existent customer
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate_hours', 2)
        ->set('hours_per_plate_minutes', 30)
        ->set('labor_hours', 1.0)
        ->call('save')
        ->assertHasErrors(['customer_id']);
})->coversNothing();

\test('validates material exists', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', 99999) // Non-existent material
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate_hours', 2)
        ->set('hours_per_plate_minutes', 30)
        ->set('labor_hours', 1.0)
        ->call('save')
        ->assertHasErrors(['material_id']);
})->coversNothing();

\test('redirects to show page after creation', function () {
    $response = Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test Print Job')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate_hours', 2)
        ->set('hours_per_plate_minutes', 30)
        ->set('labor_hours', 1.0)
        ->call('save');

    $printJob = PrintJob::latest()->first();
    $response->assertRedirect(\route('print-jobs.show', $printJob));
})->coversNothing();

\test('creates order sequence if missing', function () {
    $year = (int) \now()->year;

    // Delete any existing sequence
    PrintOrderSequence::where('year', $year)->delete();

    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test Print Job')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate_hours', 2)
        ->set('hours_per_plate_minutes', 30)
        ->set('labor_hours', 1.0)
        ->call('save');

    // Sequence should be created
    $sequence = PrintOrderSequence::where('year', $year)->first();
    \expect($sequence)->not->toBeNull()
        ->and($sequence->last_number)->toBe(1);
})->coversNothing();

\test('order number format is correct', function () {
    $year = (int) \now()->year;

    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Test Print Job')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate_hours', 2)
        ->set('hours_per_plate_minutes', 30)
        ->set('labor_hours', 1.0)
        ->call('save');

    $printJob = PrintJob::latest()->first();
    \expect($printJob->order_no)->toMatch('/^' . $year . '-\d{4}$/');
})->coversNothing();
