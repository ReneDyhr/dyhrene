<?php

declare(strict_types=1);

use App\Domain\Printing\PrintJobCalculator;
use App\Models\PrintCustomer;
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

\test('handles maximum allowed values', function () {
    $calculator = new PrintJobCalculator();
    $input = [
        'pieces_per_plate' => 100,
        'plates' => 10,
        'grams_per_plate' => 999,
        'hours_per_plate' => 999,
        'labor_hours' => 999,
        'is_first_time_order' => false,
        'electricity_rate_dkk_per_kwh' => 2.5,
        'wage_rate_dkk_per_hour' => 200.0,
        'first_time_fee_dkk' => 50.0,
        'default_avance_pct' => 50.0,
        'price_per_kg_dkk' => 300.0,
        'waste_factor_pct' => 10.0,
        'avg_kwh_per_hour' => 0.5,
    ];

    $result = $calculator->calculate($input);

    \expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['totals', 'costs', 'pricing', 'profit']);
})->covers(PrintJobCalculator::class);

\test('handles minimum values', function () {
    $calculator = new PrintJobCalculator();
    $input = [
        'pieces_per_plate' => 1,
        'plates' => 1,
        'grams_per_plate' => 1,
        'hours_per_plate' => 0.1,
        'labor_hours' => 0.1,
        'is_first_time_order' => false,
        'electricity_rate_dkk_per_kwh' => 2.5,
        'wage_rate_dkk_per_hour' => 200.0,
        'first_time_fee_dkk' => 50.0,
        'default_avance_pct' => 50.0,
        'price_per_kg_dkk' => 300.0,
        'waste_factor_pct' => 10.0,
        'avg_kwh_per_hour' => 0.5,
    ];

    $result = $calculator->calculate($input);

    \expect($result)->toBeArray()
        ->and($result['totals']['total_pieces'])->toBe(1.0)
        ->and($result['totals']['total_grams'])->toBe(1.0);
})->covers(PrintJobCalculator::class);

\test('handles zero values where allowed', function () {
    $calculator = new PrintJobCalculator();
    $input = [
        'pieces_per_plate' => 10,
        'plates' => 2,
        'grams_per_plate' => 0,
        'hours_per_plate' => 0,
        'labor_hours' => 0,
        'is_first_time_order' => false,
        'electricity_rate_dkk_per_kwh' => 2.5,
        'wage_rate_dkk_per_hour' => 200.0,
        'first_time_fee_dkk' => 50.0,
        'default_avance_pct' => 50.0,
        'price_per_kg_dkk' => 300.0,
        'waste_factor_pct' => 10.0,
        'avg_kwh_per_hour' => 0.5,
    ];

    $result = $calculator->calculate($input);

    \expect($result)->toBeArray()
        ->and($result['totals']['total_grams'])->toBe(0.0)
        ->and($result['totals']['total_print_hours'])->toBe(0.0);
})->covers(PrintJobCalculator::class);
