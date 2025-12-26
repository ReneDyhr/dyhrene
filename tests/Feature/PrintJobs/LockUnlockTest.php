<?php

declare(strict_types=1);

use App\Livewire\PrintJobs\Edit;
use App\Livewire\PrintJobs\Show;
use App\Models\PrintActivityLog;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
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

    // Create a draft job
    $this->printJob = PrintJob::factory()->draft()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
        'pieces_per_plate' => 10,
        'plates' => 2,
        'grams_per_plate' => 100,
        'hours_per_plate' => 2.5,
        'labor_hours' => 1.0,
        'is_first_time_order' => false,
    ]);
});

\test('can lock a draft job', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['printJob' => $this->printJob])
        ->call('lock')
        ->assertRedirect(\route('print-jobs.show', $this->printJob));

    $this->printJob->refresh();
    \expect($this->printJob->status)->toBe('locked')
        ->and($this->printJob->locked_at)->not->toBeNull()
        ->and($this->printJob->calc_snapshot)->not->toBeNull();
})->coversNothing();

\test('creates snapshot when locking', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['printJob' => $this->printJob])
        ->call('lock');

    $this->printJob->refresh();
    $snapshot = $this->printJob->calc_snapshot;
    
    \expect($snapshot)->toBeArray()
        ->and($snapshot)->toHaveKeys(['totals', 'costs', 'pricing', 'profit']);
})->coversNothing();

\test('can unlock a locked job', function () {
    $this->printJob->lock();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->call('unlock')
        ->assertRedirect(\route('print-jobs.edit', $this->printJob));

    $this->printJob->refresh();
    \expect($this->printJob->status)->toBe('draft')
        ->and($this->printJob->locked_at)->toBeNull()
        ->and($this->printJob->calc_snapshot)->toBeNull();
})->coversNothing();

\test('clears snapshot when unlocking', function () {
    // Lock the job first
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['printJob' => $this->printJob])
        ->call('lock');
    
    $this->printJob->refresh();
    \expect($this->printJob->calc_snapshot)->not->toBeNull();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->call('unlock');

    $this->printJob->refresh();
    \expect($this->printJob->calc_snapshot)->toBeNull();
})->coversNothing();

\test('preserves field values on unlock', function () {
    $originalPieces = $this->printJob->pieces_per_plate;
    $originalPlates = $this->printJob->plates;
    $originalGrams = $this->printJob->grams_per_plate;

    $this->printJob->lock();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->call('unlock');

    $this->printJob->refresh();
    \expect($this->printJob->pieces_per_plate)->toBe($originalPieces)
        ->and($this->printJob->plates)->toBe($originalPlates)
        ->and($this->printJob->grams_per_plate)->toBe($originalGrams);
})->coversNothing();

\test('logs activity when locking', function () {
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['printJob' => $this->printJob])
        ->call('lock');

    $activityLog = PrintActivityLog::where('print_job_id', $this->printJob->id)
        ->where('action', 'locked')
        ->first();

    \expect($activityLog)->not->toBeNull()
        ->and($activityLog->user_id)->toBe($this->user->id);
})->coversNothing();

\test('logs activity when unlocking', function () {
    // Lock the job first
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['printJob' => $this->printJob])
        ->call('lock');

    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->call('unlock');

    $activityLog = PrintActivityLog::where('print_job_id', $this->printJob->id)
        ->where('action', 'unlocked')
        ->first();

    \expect($activityLog)->not->toBeNull()
        ->and($activityLog->user_id)->toBe($this->user->id);
})->coversNothing();

\test('locked jobs cannot be edited', function () {
    // Lock the job first
    Livewire::actingAs($this->user)
        ->test(Edit::class, ['printJob' => $this->printJob])
        ->call('lock');

    // Refresh the job to get the locked state
    $this->printJob->refresh();
    
    // Try to access edit page - should redirect to show
    $response = Livewire::actingAs($this->user)
        ->test(Edit::class, ['printJob' => $this->printJob]);

    // The mount method should redirect locked jobs to show page
    $response->assertRedirect(\route('print-jobs.show', $this->printJob));
})->coversNothing();
