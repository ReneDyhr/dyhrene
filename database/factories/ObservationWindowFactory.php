<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ObservationSourceEnum;
use App\Models\ObservationWindow;
use App\Models\Site;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObservationWindow>
 */
class ObservationWindowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'species_id' => Species::factory(),
            'window_start' => \fake()->dateTime(),
            'source' => ObservationSourceEnum::Birdnet->value,
            'records' => \fake()->numberBetween(1, 200),
            'max_confidence' => \fake()->randomFloat(4, 0, 1),
        ];
    }
}
