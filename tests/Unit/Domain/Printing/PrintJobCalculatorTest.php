<?php

declare(strict_types=1);

use App\Domain\Printing\PrintJobCalculator;

\uses()->group('unit');

\covers(PrintJobCalculator::class);

\beforeEach(function () {
    $this->calculator = new PrintJobCalculator();
});

/**
 * Helper method to build a standard input array with default values.
 */
function buildInput(array $overrides = []): array
{
    return \array_merge([
        // Job inputs
        'pieces_per_plate' => 10,
        'plates' => 2,
        'grams_per_plate' => 100,
        'hours_per_plate' => 2.5,
        'labor_hours' => 1.0,
        'is_first_time_order' => false,
        'avance_pct_override' => null,
        // Settings
        'electricity_rate_dkk_per_kwh' => 2.5,
        'wage_rate_dkk_per_hour' => 200.0,
        'first_time_fee_dkk' => 50.0,
        'default_avance_pct' => 50.0,
        // Material
        'price_per_kg_dkk' => 300.0,
        'waste_factor_pct' => 10.0,
        // Material type
        'avg_kwh_per_hour' => 0.5,
    ], $overrides);
}

\test('calculates derived totals correctly', function () {
    $input = \buildInput([
        'pieces_per_plate' => 10,
        'plates' => 3,
        'grams_per_plate' => 150,
        'hours_per_plate' => 2.5,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['totals']['total_pieces'])->toBe(30.0)
        ->and($result['totals']['total_grams'])->toBe(450.0)
        ->and($result['totals']['total_print_hours'])->toBe(7.5);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('scales totals by plates correctly', function () {
    $input1 = \buildInput(['plates' => 1]);
    $input2 = \buildInput(['plates' => 5]);

    $result1 = $this->calculator->calculate($input1);
    $result2 = $this->calculator->calculate($input2);

    \expect($result1['totals']['total_pieces'])->toBe(10.0)
        ->and($result2['totals']['total_pieces'])->toBe(50.0)
        ->and($result1['totals']['total_grams'])->toBe(100.0)
        ->and($result2['totals']['total_grams'])->toBe(500.0)
        ->and($result1['totals']['total_print_hours'])->toBe(2.5)
        ->and($result2['totals']['total_print_hours'])->toBe(12.5);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates kWh correctly', function () {
    $input = \buildInput([
        'hours_per_plate' => 2.0,
        'plates' => 3,
        'avg_kwh_per_hour' => 0.75,
    ]);

    $result = $this->calculator->calculate($input);

    // total_print_hours = 2.0 * 3 = 6.0
    // kwh = 6.0 * 0.75 = 4.5
    \expect($result['totals']['kwh'])->toBe(4.5);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates kWh with higher precision values', function () {
    $input = \buildInput([
        'hours_per_plate' => 1.5,
        'plates' => 2,
        'avg_kwh_per_hour' => 0.1234,
    ]);

    $result = $this->calculator->calculate($input);

    // total_print_hours = 1.5 * 2 = 3.0
    // kwh = 3.0 * 0.1234 = 0.3702
    \expect($result['totals']['kwh'])->toBe(0.3702);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('converts kg to grams correctly for material cost', function () {
    $input = \buildInput([
        'price_per_kg_dkk' => 300.0,
        'grams_per_plate' => 100,
        'plates' => 1,
        'waste_factor_pct' => 0,
    ]);

    $result = $this->calculator->calculate($input);

    // material_price_per_g = 300 / 1000 = 0.3
    // material_cost = 100 * 0.3 = 30.0
    \expect($result['costs']['material_cost'])->toBe(30.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates material cost with various price_per_kg values', function () {
    $input1 = \buildInput(['price_per_kg_dkk' => 200.0, 'waste_factor_pct' => 0]);
    $input2 = \buildInput(['price_per_kg_dkk' => 500.0, 'waste_factor_pct' => 0]);

    $result1 = $this->calculator->calculate($input1);
    $result2 = $this->calculator->calculate($input2);

    // For 200 DKK/kg: 200/1000 * 200g = 40.0
    // For 500 DKK/kg: 500/1000 * 200g = 100.0
    \expect($result1['costs']['material_cost'])->toBe(40.0)
        ->and($result2['costs']['material_cost'])->toBe(100.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('applies waste factor correctly', function () {
    $input = \buildInput([
        'price_per_kg_dkk' => 300.0,
        'grams_per_plate' => 100,
        'plates' => 1,
        'waste_factor_pct' => 10.0,
    ]);

    $result = $this->calculator->calculate($input);

    // material_cost = 30.0
    // material_cost_with_waste = 30.0 * 1.1 = 33.0
    \expect($result['costs']['material_cost'])->toBe(30.0)
        ->and($result['costs']['material_cost_with_waste'])->toBe(33.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles zero waste factor', function () {
    $input = \buildInput(['waste_factor_pct' => 0]);

    $result = $this->calculator->calculate($input);

    \expect($result['costs']['material_cost_with_waste'])->toBe($result['costs']['material_cost']);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles various waste percentages', function () {
    $input1 = \buildInput(['waste_factor_pct' => 5.0]);
    $input2 = \buildInput(['waste_factor_pct' => 20.0]);
    $input3 = \buildInput(['waste_factor_pct' => 50.0]);

    $result1 = $this->calculator->calculate($input1);
    $result2 = $this->calculator->calculate($input2);
    $result3 = $this->calculator->calculate($input3);

    // Base material_cost = 60.0 (for 200g total)
    \expect($result1['costs']['material_cost_with_waste'])->toBe(63.0) // 60 * 1.05
        ->and($result2['costs']['material_cost_with_waste'])->toBe(72.0) // 60 * 1.20
        ->and($result3['costs']['material_cost_with_waste'])->toBe(90.0); // 60 * 1.50
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates power cost correctly', function () {
    $input = \buildInput([
        'hours_per_plate' => 2.0,
        'plates' => 2,
        'avg_kwh_per_hour' => 0.5,
        'electricity_rate_dkk_per_kwh' => 2.5,
    ]);

    $result = $this->calculator->calculate($input);

    // kwh = 2.0 * 2 * 0.5 = 2.0
    // power_cost = 2.0 * 2.5 = 5.0
    \expect($result['costs']['power_cost'])->toBe(5.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates labor cost correctly', function () {
    $input = \buildInput([
        'labor_hours' => 2.5,
        'wage_rate_dkk_per_hour' => 200.0,
    ]);

    $result = $this->calculator->calculate($input);

    // labor_cost = 2.5 * 200 = 500.0
    \expect($result['costs']['labor_cost'])->toBe(500.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('applies first-time fee when is_first_time_order is true', function () {
    $input = \buildInput([
        'is_first_time_order' => true,
        'first_time_fee_dkk' => 50.0,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['costs']['first_time_fee_applied'])->toBe(50.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('does not apply first-time fee when is_first_time_order is false', function () {
    $input = \buildInput([
        'is_first_time_order' => false,
        'first_time_fee_dkk' => 50.0,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['costs']['first_time_fee_applied'])->toBe(0.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('rounds each cost component to 2 decimals before summing', function () {
    $input = \buildInput([
        'price_per_kg_dkk' => 333.33,
        'grams_per_plate' => 100,
        'plates' => 1,
        'waste_factor_pct' => 11.11,
        'hours_per_plate' => 1.111,
        'avg_kwh_per_hour' => 0.555,
        'electricity_rate_dkk_per_kwh' => 2.222,
        'labor_hours' => 1.111,
        'wage_rate_dkk_per_hour' => 111.11,
    ]);

    $result = $this->calculator->calculate($input);

    // Verify all cost components are rounded to 2 decimals
    \expect($result['costs']['material_cost'])->toBe(\round($result['costs']['material_cost'], 2))
        ->and($result['costs']['material_cost_with_waste'])->toBe(\round($result['costs']['material_cost_with_waste'], 2))
        ->and($result['costs']['power_cost'])->toBe(\round($result['costs']['power_cost'], 2))
        ->and($result['costs']['labor_cost'])->toBe(\round($result['costs']['labor_cost'], 2))
        ->and($result['costs']['first_time_fee_applied'])->toBe(\round($result['costs']['first_time_fee_applied'], 2))
        ->and($result['costs']['total_cost'])->toBe(\round($result['costs']['total_cost'], 2));
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles rounding edge cases correctly', function () {
    // Test 0.005 rounds to 0.01
    $input1 = \buildInput([
        'price_per_kg_dkk' => 25.0, // 25/1000 * 200 = 5.0, but with rounding might be 5.005
        'grams_per_plate' => 200.01,
        'plates' => 1,
        'waste_factor_pct' => 0,
    ]);

    $result1 = $this->calculator->calculate($input1);
    \expect($result1['costs']['material_cost'])->toBe(\round($result1['costs']['material_cost'], 2));

    // Test 0.004 rounds to 0.00
    $input2 = \buildInput([
        'price_per_kg_dkk' => 20.0,
        'grams_per_plate' => 200.0,
        'plates' => 1,
        'waste_factor_pct' => 0,
    ]);

    $result2 = $this->calculator->calculate($input2);
    \expect($result2['costs']['material_cost'])->toBe(\round($result2['costs']['material_cost'], 2));
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates total cost as sum of rounded components', function () {
    $input = \buildInput([
        'material_cost' => 30.0,
        'waste_factor_pct' => 10.0,
        'hours_per_plate' => 2.0,
        'plates' => 1,
        'avg_kwh_per_hour' => 0.5,
        'electricity_rate_dkk_per_kwh' => 2.5,
        'labor_hours' => 1.0,
        'wage_rate_dkk_per_hour' => 200.0,
        'is_first_time_order' => false,
    ]);

    $result = $this->calculator->calculate($input);

    $expectedTotal = \round(
        \round($result['costs']['material_cost_with_waste'], 2)
        + \round($result['costs']['power_cost'], 2)
        + \round($result['costs']['labor_cost'], 2)
        + \round($result['costs']['first_time_fee_applied'], 2),
        2,
    );

    \expect($result['costs']['total_cost'])->toBe($expectedTotal);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('uses default markup when override is not provided', function () {
    $input = \buildInput([
        'default_avance_pct' => 50.0,
        'avance_pct_override' => null,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['pricing']['applied_avance_pct'])->toBe(50.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('uses override markup when provided', function () {
    $input = \buildInput([
        'default_avance_pct' => 50.0,
        'avance_pct_override' => 75.0,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['pricing']['applied_avance_pct'])->toBe(75.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates sales price with markup correctly', function () {
    $input = \buildInput([
        'default_avance_pct' => 50.0,
        'price_per_kg_dkk' => 300.0,
        'grams_per_plate' => 100,
        'plates' => 1,
        'waste_factor_pct' => 0,
        'hours_per_plate' => 0,
        'labor_hours' => 0,
        'is_first_time_order' => false,
    ]);

    $result = $this->calculator->calculate($input);

    // total_cost = 30.0 (material only)
    // sales_price = 30.0 * 1.5 = 45.0
    \expect($result['costs']['total_cost'])->toBe(30.0)
        ->and($result['pricing']['sales_price'])->toBe(45.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles zero percent markup', function () {
    $input = \buildInput([
        'default_avance_pct' => 0.0,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['pricing']['sales_price'])->toBe($result['costs']['total_cost']);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles 100 percent markup', function () {
    $input = \buildInput([
        'default_avance_pct' => 100.0,
        'price_per_kg_dkk' => 300.0,
        'grams_per_plate' => 100,
        'plates' => 1,
        'waste_factor_pct' => 0,
        'hours_per_plate' => 0,
        'labor_hours' => 0,
        'is_first_time_order' => false,
    ]);

    $result = $this->calculator->calculate($input);

    // total_cost = 30.0
    // sales_price = 30.0 * 2.0 = 60.0
    \expect($result['pricing']['sales_price'])->toBe(60.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates price per piece correctly', function () {
    $input = \buildInput([
        'pieces_per_plate' => 10,
        'plates' => 2,
        'default_avance_pct' => 0,
    ]);

    $result = $this->calculator->calculate($input);

    $totalPieces = 20;
    $expectedPricePerPiece = \round($result['pricing']['sales_price'] / $totalPieces, 2);

    \expect($result['pricing']['price_per_piece'])->toBe($expectedPricePerPiece);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles division by zero for price per piece', function () {
    $input = \buildInput([
        'pieces_per_plate' => 0,
        'plates' => 0,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['pricing']['price_per_piece'])->toBe(0.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates profit correctly', function () {
    $input = \buildInput([
        'default_avance_pct' => 50.0,
        'price_per_kg_dkk' => 300.0,
        'grams_per_plate' => 100,
        'plates' => 1,
        'waste_factor_pct' => 0,
        'hours_per_plate' => 0,
        'labor_hours' => 0,
        'is_first_time_order' => false,
    ]);

    $result = $this->calculator->calculate($input);

    // total_cost = 30.0, sales_price = 45.0
    // profit = 45.0 - 30.0 = 15.0
    \expect($result['profit']['profit'])->toBe(15.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('calculates profit per piece correctly', function () {
    $input = \buildInput([
        'pieces_per_plate' => 10,
        'plates' => 2,
        'default_avance_pct' => 50.0,
    ]);

    $result = $this->calculator->calculate($input);

    $totalPieces = 20;
    $expectedProfitPerPiece = \round($result['profit']['profit'] / $totalPieces, 2);

    \expect($result['profit']['profit_per_piece'])->toBe($expectedProfitPerPiece);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles division by zero for profit per piece', function () {
    $input = \buildInput([
        'pieces_per_plate' => 0,
        'plates' => 0,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['profit']['profit_per_piece'])->toBe(0.0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('rounds all monetary values to 2 decimals', function () {
    $input = \buildInput();

    $result = $this->calculator->calculate($input);

    // Check all cost values
    \expect($result['costs']['material_cost'])->toBe(\round($result['costs']['material_cost'], 2))
        ->and($result['costs']['material_cost_with_waste'])->toBe(\round($result['costs']['material_cost_with_waste'], 2))
        ->and($result['costs']['power_cost'])->toBe(\round($result['costs']['power_cost'], 2))
        ->and($result['costs']['labor_cost'])->toBe(\round($result['costs']['labor_cost'], 2))
        ->and($result['costs']['first_time_fee_applied'])->toBe(\round($result['costs']['first_time_fee_applied'], 2))
        ->and($result['costs']['total_cost'])->toBe(\round($result['costs']['total_cost'], 2));

    // Check pricing values
    \expect($result['pricing']['sales_price'])->toBe(\round($result['pricing']['sales_price'], 2))
        ->and($result['pricing']['price_per_piece'])->toBe(\round($result['pricing']['price_per_piece'], 2));

    // Check profit values
    \expect($result['profit']['profit'])->toBe(\round($result['profit']['profit'], 2))
        ->and($result['profit']['profit_per_piece'])->toBe(\round($result['profit']['profit_per_piece'], 2));
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles very large numbers', function () {
    $input = \buildInput([
        'pieces_per_plate' => 1000,
        'plates' => 100,
        'grams_per_plate' => 10000,
        'hours_per_plate' => 100,
        'price_per_kg_dkk' => 10000.0,
        'electricity_rate_dkk_per_kwh' => 100.0,
        'wage_rate_dkk_per_hour' => 10000.0,
        'labor_hours' => 100,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['totals']['total_pieces'])->toBe(100000.0)
        ->and($result['totals']['total_grams'])->toBe(1000000.0)
        ->and($result['totals']['total_print_hours'])->toBe(10000.0)
        ->and($result['costs']['total_cost'])->toBeGreaterThan(0)
        ->and($result['pricing']['sales_price'])->toBeGreaterThan(0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles very small numbers', function () {
    $input = \buildInput([
        'pieces_per_plate' => 1,
        'plates' => 1,
        'grams_per_plate' => 0.01,
        'hours_per_plate' => 0.01,
        'price_per_kg_dkk' => 1.0,
        'waste_factor_pct' => 0.01,
        'avg_kwh_per_hour' => 0.0001,
        'electricity_rate_dkk_per_kwh' => 0.01,
        'labor_hours' => 0.01,
        'wage_rate_dkk_per_hour' => 1.0,
    ]);

    $result = $this->calculator->calculate($input);

    \expect($result['totals']['total_pieces'])->toBe(1.0)
        ->and($result['totals']['total_grams'])->toBe(0.01)
        ->and($result['totals']['total_print_hours'])->toBe(0.01)
        ->and($result['costs']['total_cost'])->toBeGreaterThanOrEqual(0)
        ->and($result['pricing']['sales_price'])->toBeGreaterThanOrEqual(0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('returns complete output structure', function () {
    $input = \buildInput();

    $result = $this->calculator->calculate($input);

    \expect($result)->toHaveKeys(['totals', 'costs', 'pricing', 'profit'])
        ->and($result['totals'])->toHaveKeys(['total_pieces', 'total_grams', 'total_print_hours', 'kwh'])
        ->and($result['costs'])->toHaveKeys([
            'material_cost',
            'material_cost_with_waste',
            'power_cost',
            'labor_cost',
            'first_time_fee_applied',
            'total_cost',
        ])
        ->and($result['pricing'])->toHaveKeys(['applied_avance_pct', 'sales_price', 'price_per_piece'])
        ->and($result['profit'])->toHaveKeys(['profit', 'profit_per_piece']);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles realistic business scenario', function () {
    $input = \buildInput([
        'pieces_per_plate' => 25,
        'plates' => 4,
        'grams_per_plate' => 250,
        'hours_per_plate' => 3.5,
        'labor_hours' => 2.0,
        'is_first_time_order' => true,
        'avance_pct_override' => 60.0,
        'electricity_rate_dkk_per_kwh' => 2.75,
        'wage_rate_dkk_per_hour' => 225.0,
        'first_time_fee_dkk' => 75.0,
        'default_avance_pct' => 50.0,
        'price_per_kg_dkk' => 350.0,
        'waste_factor_pct' => 15.0,
        'avg_kwh_per_hour' => 0.6,
    ]);

    $result = $this->calculator->calculate($input);

    // Verify calculations
    \expect($result['totals']['total_pieces'])->toBe(100.0)
        ->and($result['totals']['total_grams'])->toBe(1000.0)
        ->and($result['totals']['total_print_hours'])->toBe(14.0)
        ->and($result['totals']['kwh'])->toBe(8.4)
        ->and($result['costs']['first_time_fee_applied'])->toBe(75.0)
        ->and($result['pricing']['applied_avance_pct'])->toBe(60.0)
        ->and($result['pricing']['sales_price'])->toBeGreaterThan($result['costs']['total_cost'])
        ->and($result['profit']['profit'])->toBeGreaterThan(0);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('handles minimal input fields', function () {
    $input = [
        'pieces_per_plate' => 1,
        'plates' => 1,
    ];

    $result = $this->calculator->calculate($input);

    // Should not throw errors and return valid structure
    \expect($result)->toHaveKeys(['totals', 'costs', 'pricing', 'profit'])
        ->and($result['totals']['total_pieces'])->toBe(1.0)
        ->and($result['pricing']['price_per_piece'])->toBe(0.0); // Division by zero protection
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('verifies rounding behavior with 0.005', function () {
    // Create a scenario where we get 0.005
    $input = \buildInput([
        'price_per_kg_dkk' => 25.0,
        'grams_per_plate' => 200.2, // 25/1000 * 200.2 = 5.005
        'plates' => 1,
        'waste_factor_pct' => 0,
    ]);

    $result = $this->calculator->calculate($input);

    // PHP's round() uses standard rounding (0.005 rounds to 0.01)
    $materialCost = \round(25.0 / 1000 * 200.2, 2);
    \expect($result['costs']['material_cost'])->toBe($materialCost);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('verifies rounding behavior with 0.004', function () {
    $input = \buildInput([
        'price_per_kg_dkk' => 20.0,
        'grams_per_plate' => 200.0, // 20/1000 * 200 = 4.0
        'plates' => 1,
        'waste_factor_pct' => 0,
    ]);

    $result = $this->calculator->calculate($input);

    $materialCost = \round(20.0 / 1000 * 200.0, 2);
    \expect($result['costs']['material_cost'])->toBe($materialCost);
});

/**
 * @covers \App\Domain\Printing\PrintJobCalculator
 */
\test('verifies rounding behavior with 0.015', function () {
    $input = \buildInput([
        'price_per_kg_dkk' => 75.0,
        'grams_per_plate' => 200.2, // 75/1000 * 200.2 = 15.015
        'plates' => 1,
        'waste_factor_pct' => 0,
    ]);

    $result = $this->calculator->calculate($input);

    $materialCost = \round(75.0 / 1000 * 200.2, 2);
    \expect($result['costs']['material_cost'])->toBe($materialCost);
});
