<?php

declare(strict_types=1);

use App\Livewire\PrintJobs\Create;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\PrintSetting;
use App\Models\User;

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

\test('soft-deleted customers do not appear in dropdowns', function () {
    $deletedCustomer = PrintCustomer::factory()->create();
    $deletedCustomer->delete();

    // Test that active() scope excludes soft-deleted customers
    $activeCustomers = PrintCustomer::query()->active()->get();
    $activeCustomerIds = $activeCustomers->pluck('id')->toArray();

    \expect(\in_array($deletedCustomer->id, $activeCustomerIds, true))->toBeFalse();
})->covers(PrintCustomer::class);

\test('soft-deleted materials do not appear in dropdowns', function () {
    $deletedMaterial = PrintMaterial::factory()->create([
        'material_type_id' => $this->materialType->id,
    ]);
    $deletedMaterial->delete();

    // Test that active() scope excludes soft-deleted materials
    $activeMaterials = PrintMaterial::query()->active()->get();
    $activeMaterialIds = $activeMaterials->pluck('id')->toArray();

    \expect(\in_array($deletedMaterial->id, $activeMaterialIds, true))->toBeFalse();
})->covers(PrintMaterial::class);

\test('existing jobs with soft-deleted relations still display', function () {
    $job = PrintJob::factory()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
    ]);

    $this->customer->delete();
    $this->material->delete();

    $job->refresh();
    // Load with trashed to access soft-deleted relations
    $job->load(['customer' => function ($query) {
        $query->withTrashed();
    }, 'material' => function ($query) {
        $query->withTrashed();
    }]);

    // Should still be able to access via withTrashed
    \expect($job->customer)->not->toBeNull()
        ->and($job->material)->not->toBeNull();
})->covers(PrintJob::class);

\test('locked jobs with soft-deleted material display from snapshot', function () {
    $job = PrintJob::factory()->locked()->create([
        'customer_id' => $this->customer->id,
        'material_id' => $this->material->id,
    ]);

    $this->material->delete();

    $job->refresh();

    // Locked jobs use snapshot, so material data should be in snapshot
    \expect($job->calc_snapshot)->not->toBeNull()
        ->and($job->calc_snapshot)->toBeArray();
})->covers(PrintJob::class);
