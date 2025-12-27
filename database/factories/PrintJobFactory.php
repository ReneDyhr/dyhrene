<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrintJob>
 */
class PrintJobFactory extends Factory
{
    protected $model = PrintJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) \now()->year;
        $sequence = \App\Models\PrintOrderSequence::firstOrCreate(
            ['year' => $year],
            ['last_number' => 0],
        );
        $sequence->increment('last_number');
        $sequence->refresh();
        $orderNo = \sprintf('%d-%04d', $year, $sequence->last_number);

        return [
            'order_no' => $orderNo,
            'date' => \fake()->date(),
            'description' => \fake()->sentence(),
            'internal_notes' => \fake()->optional()->text(),
            'customer_id' => PrintCustomer::factory(),
            'material_id' => PrintMaterial::factory(),
            'pieces_per_plate' => \fake()->numberBetween(1, 20),
            'plates' => \fake()->numberBetween(1, 10),
            'grams_per_plate' => \fake()->randomFloat(2, 10, 500),
            'hours_per_plate' => \fake()->randomFloat(3, 0.5, 24),
            'labor_hours' => \fake()->randomFloat(3, 0, 10),
            'is_first_time_order' => \fake()->boolean(20),
            'avance_pct_override' => \fake()->optional()->randomFloat(2, 20, 100),
            'status' => \fake()->randomElement(['draft', 'locked']),
            'locked_at' => \fake()->optional()->dateTime(),
            'calc_snapshot' => \fake()->optional()->randomElement([null, ['total_cost' => 100.50, 'sales_price' => 150.75]]),
        ];
    }

    /**
     * Indicate that the print job is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn(): array => [
            'status' => 'draft',
            'locked_at' => null,
            'calc_snapshot' => null,
        ]);
    }

    /**
     * Indicate that the print job is locked.
     */
    public function locked(): static
    {
        return $this->state(fn(): array => [
            'status' => 'locked',
            'locked_at' => \now(),
            'calc_snapshot' => [
                'input' => [
                    'pieces_per_plate' => \fake()->numberBetween(1, 20),
                    'plates' => \fake()->numberBetween(1, 10),
                    'grams_per_plate' => \fake()->randomFloat(2, 10, 500),
                    'hours_per_plate' => \fake()->randomFloat(3, 0.5, 24),
                    'labor_hours' => \fake()->randomFloat(3, 0, 10),
                    'is_first_time_order' => \fake()->boolean(20),
                    'avance_pct_override' => null,
                ],
                'rates' => [
                    'electricity_rate_dkk_per_kwh' => \fake()->randomFloat(4, 1.0, 5.0),
                    'wage_rate_dkk_per_hour' => \fake()->randomFloat(2, 100, 500),
                    'first_time_fee_dkk' => \fake()->randomFloat(2, 0, 500),
                    'applied_avance_pct' => \fake()->randomFloat(2, 20, 100),
                    'material_price_per_kg_dkk' => \fake()->randomFloat(2, 100, 500),
                    'material_waste_factor_pct' => \fake()->randomFloat(2, 0, 10),
                    'material_type_avg_kwh_per_hour' => \fake()->randomFloat(4, 0.05, 0.20),
                    'material_name' => \fake()->words(2, true),
                    'material_type_name' => \fake()->randomElement(['PLA', 'PETG', 'TPU', 'ABS', 'ASA']),
                ],
                'totals' => [
                    'total_pieces' => \fake()->numberBetween(10, 200),
                    'total_grams' => \fake()->randomFloat(2, 100, 10000),
                    'total_print_hours' => \fake()->randomFloat(3, 1, 100),
                    'kwh' => \fake()->randomFloat(4, 0.1, 20),
                ],
                'costs' => [
                    'material_cost' => \fake()->randomFloat(2, 10, 500),
                    'material_cost_with_waste' => \fake()->randomFloat(2, 10, 550),
                    'power_cost' => \fake()->randomFloat(2, 1, 100),
                    'labor_cost' => \fake()->randomFloat(2, 50, 1000),
                    'first_time_fee_applied' => \fake()->randomFloat(2, 0, 500),
                    'total_cost' => \fake()->randomFloat(2, 100, 2000),
                ],
                'pricing' => [
                    'applied_avance_pct' => \fake()->randomFloat(2, 20, 100),
                    'sales_price' => \fake()->randomFloat(2, 150, 3000),
                    'price_per_piece' => \fake()->randomFloat(2, 1, 50),
                ],
                'profit' => [
                    'profit' => \fake()->randomFloat(2, 10, 1000),
                    'profit_per_piece' => \fake()->randomFloat(2, 0.1, 20),
                ],
            ],
        ]);
    }
}
