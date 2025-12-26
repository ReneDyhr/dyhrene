<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintCustomer;
use App\Models\PrintMaterial;
use App\Models\PrintJob;
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
        return [
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
        return $this->state(fn (): array => [
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
        return $this->state(fn (): array => [
            'status' => 'locked',
            'locked_at' => \now(),
            'calc_snapshot' => [
                'total_cost' => \fake()->randomFloat(2, 50, 500),
                'sales_price' => \fake()->randomFloat(2, 75, 750),
            ],
        ]);
    }
}
