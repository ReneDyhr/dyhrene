<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintMaterialType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrintMaterialType>
 */
class PrintMaterialTypeFactory extends Factory
{
    protected $model = PrintMaterialType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => \fake()->unique()->randomElement(['PLA', 'PETG', 'TPU', 'ABS', 'ASA']),
            'avg_kwh_per_hour' => \fake()->randomFloat(4, 0.05, 0.20),
        ];
    }
}

