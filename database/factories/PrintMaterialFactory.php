<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrintMaterial>
 */
class PrintMaterialFactory extends Factory
{
    protected $model = PrintMaterial::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_type_id' => PrintMaterialType::factory(),
            'name' => \fake()->words(2, true),
            'price_per_kg_dkk' => \fake()->randomFloat(2, 100, 500),
            'waste_factor_pct' => \fake()->randomFloat(2, 0, 10),
            'notes' => \fake()->optional()->sentence(),
        ];
    }
}

