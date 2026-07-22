<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Species>
 */
class SpeciesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'common_name' => \fake()->unique()->word(),
            'scientific_name' => \fake()->unique()->word(),
            'user_id' => User::factory(),
        ];
    }
}
