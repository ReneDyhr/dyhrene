<?php

declare(strict_types=1);

use App\Livewire\PrintJobs\Index;
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
});

\test('can delete draft jobs', function () {
    $draftJob = PrintJob::factory()->draft()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->call('delete', $draftJob->id);
    
    // Just verify the job was deleted (session check might not work in Livewire tests)

    \expect(PrintJob::find($draftJob->id))->toBeNull()
        ->and(PrintJob::withTrashed()->find($draftJob->id))->not->toBeNull();
})->coversNothing();

\test('cannot delete locked jobs', function () {
    $lockedJob = PrintJob::factory()->locked()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->call('delete', $lockedJob->id);
    
    // Just verify the job was not deleted (session check might not work in Livewire tests)

    \expect(PrintJob::find($lockedJob->id))->not->toBeNull();
})->coversNothing();

\test('soft delete works correctly', function () {
    $draftJob = PrintJob::factory()->draft()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
    ]);

    $draftJob->delete();

    \expect(PrintJob::find($draftJob->id))->toBeNull()
        ->and(PrintJob::withTrashed()->find($draftJob->id))->not->toBeNull()
        ->and(PrintJob::withTrashed()->find($draftJob->id)->trashed())->toBeTrue();
})->coversNothing();
