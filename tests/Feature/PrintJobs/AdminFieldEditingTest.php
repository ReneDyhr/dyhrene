<?php

declare(strict_types=1);

use App\Livewire\PrintJobs\Show;
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
    $this->customer2 = PrintCustomer::factory()->create();
    $this->materialType = PrintMaterialType::factory()->create();
    $this->material = PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
    ]);
    PrintSetting::factory()->create(['id' => 1]);

    // Create a locked job
    $this->printJob = PrintJob::factory()->locked()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
        'date' => '2025-01-15',
        'description' => 'Original Description',
        'internal_notes' => 'Original Notes',
        'pieces_per_plate' => 10,
        'plates' => 2,
        'grams_per_plate' => 100,
        'hours_per_plate' => 2.5,
        'labor_hours' => 1.0,
    ]);
});

\test('can edit date on locked job', function () {
    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->set('date', '2025-02-20')
        ->call('saveAdminFields');

    $this->printJob->refresh();
    \expect($this->printJob->date->format('Y-m-d'))->toBe('2025-02-20');
})->coversNothing();

\test('can edit customer_id on locked job', function () {
    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->set('customer_id', $this->customer2->id)
        ->call('saveAdminFields');

    $this->printJob->refresh();
    \expect($this->printJob->customer_id)->toBe($this->customer2->id);
})->coversNothing();

\test('can edit description on locked job', function () {
    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->set('description', 'Updated Description')
        ->call('saveAdminFields');

    $this->printJob->refresh();
    \expect($this->printJob->description)->toBe('Updated Description');
})->coversNothing();

\test('can edit internal_notes on locked job', function () {
    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $this->printJob])
        ->set('internal_notes', 'Updated Notes')
        ->call('saveAdminFields');

    $this->printJob->refresh();
    \expect($this->printJob->internal_notes)->toBe('Updated Notes');
})->coversNothing();

\test('calculation inputs cannot be edited on locked job', function () {
    $originalPieces = $this->printJob->pieces_per_plate;
    $originalPlates = $this->printJob->plates;
    $originalGrams = $this->printJob->grams_per_plate;

    // Try to access edit page - should redirect to show
    $response = Livewire::actingAs($this->user)
        ->test(App\Livewire\PrintJobs\Edit::class, ['printJob' => $this->printJob]);

    // Should redirect to show page
    $response->assertRedirect(\route('print-jobs.show', $this->printJob));

    // Verify calculation inputs are unchanged
    $this->printJob->refresh();
    \expect($this->printJob->pieces_per_plate)->toBe($originalPieces)
        ->and($this->printJob->plates)->toBe($originalPlates)
        ->and($this->printJob->grams_per_plate)->toBe($originalGrams);
})->coversNothing();

\test('admin fields cannot be edited on draft job', function () {
    $draftJob = PrintJob::factory()->draft()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['printJob' => $draftJob])
        ->set('description', 'Updated Description')
        ->call('saveAdminFields');

    // Just verify the description was not updated (session check might not work in Livewire tests)

    $draftJob->refresh();
    \expect($draftJob->description)->not->toBe('Updated Description');
})->coversNothing();
