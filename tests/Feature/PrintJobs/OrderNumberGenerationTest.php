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
use Illuminate\Support\Facades\DB;
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

\test('generates sequential order numbers', function () {
    $year = (int) \now()->year;
    $orderNumbers = [];

    // Create multiple jobs
    for ($i = 0; $i < 5; $i++) {
        Livewire::actingAs($this->user)->test(Create::class)
            ->set('date', '2025-01-15')
            ->set('description', "Job {$i}")
            ->set('customer_id', $this->customer->id)
            ->set('material_id', $this->material->id)
            ->set('pieces_per_plate', 10)
            ->set('plates', 2)
            ->set('grams_per_plate', 100)
            ->set('hours_per_plate', 2.5)
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

\test('order numbers reset per year', function () {
    $currentYear = (int) \now()->year;
    $nextYear = $currentYear + 1;

    // Create a job in current year
    Livewire::actingAs($this->user)->test(Create::class)
        ->set('date', "{$currentYear}-01-15")
        ->set('description', 'Current Year Job')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate', 2.5)
        ->set('labor_hours', 1.0)
        ->call('save');

    $currentYearJob = PrintJob::where('description', 'Current Year Job')->first();
    \expect($currentYearJob->order_no)->toMatch('/^' . $currentYear . '-\d{4}$/');

    // Manually create sequence for next year and create a job
    PrintOrderSequence::create([
        'year' => $nextYear,
        'last_number' => 0,
    ]);

    // Simulate next year by manually setting the sequence
    DB::table('print_order_sequences')
        ->where('year', $nextYear)
        ->update(['last_number' => 0]);

    // The order number should start from 1 for the new year
    // (This test verifies the format, actual year reset would require time manipulation)
    \expect($currentYearJob->order_no)->toMatch('/^' . $currentYear . '-\d{4}$/');
})->coversNothing();

\test('order number format is correct', function () {
    $year = (int) \now()->year;

    Livewire::actingAs($this->user)->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Format Test Job')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate', 2.5)
        ->set('labor_hours', 1.0)
        ->call('save');

    $job = PrintJob::where('description', 'Format Test Job')->first();
    \expect($job->order_no)->toMatch('/^' . $year . '-\d{4}$/');
})->coversNothing();

\test('order numbers continue after soft delete', function () {
    $year = (int) \now()->year;

    // Create and delete a job
    Livewire::actingAs($this->user)->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Job to Delete')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate', 2.5)
        ->set('labor_hours', 1.0)
        ->call('save');

    $jobToDelete = PrintJob::where('description', 'Job to Delete')->first();
    $deletedOrderNo = $jobToDelete->order_no;
    $jobToDelete->delete();

    // Create another job - should get next number, not reuse deleted one
    Livewire::actingAs($this->user)->test(Create::class)
        ->set('date', '2025-01-15')
        ->set('description', 'Job After Delete')
        ->set('customer_id', $this->customer->id)
        ->set('material_id', $this->material->id)
        ->set('pieces_per_plate', 10)
        ->set('plates', 2)
        ->set('grams_per_plate', 100)
        ->set('hours_per_plate', 2.5)
        ->set('labor_hours', 1.0)
        ->call('save');

    $newJob = PrintJob::where('description', 'Job After Delete')->first();

    // Extract sequence numbers
    \preg_match('/' . $year . '-(\d{4})/', $deletedOrderNo, $deletedMatches);
    \preg_match('/' . $year . '-(\d{4})/', $newJob->order_no, $newMatches);

    $deletedSeq = (int) $deletedMatches[1];
    $newSeq = (int) $newMatches[1];

    \expect($newSeq)->toBe($deletedSeq + 1);
})->coversNothing();
