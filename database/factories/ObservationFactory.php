<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Observation;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
class ObservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'species_id' => Species::factory(),
            'user_id' => User::factory(),
            'observed_at' => \fake()->date(),
        ];
    }
}
