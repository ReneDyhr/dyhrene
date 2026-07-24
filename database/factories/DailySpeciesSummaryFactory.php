<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DailySpeciesSummary;
use App\Models\Site;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailySpeciesSummary>
 */
class DailySpeciesSummaryFactory extends Factory
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
            'date' => \fake()->date(),
            'species_id' => Species::factory(),
            'windows_present' => \fake()->numberBetween(0, 144),
            'records' => \fake()->numberBetween(0, 2000),
            'sources' => ['birdnet'],
            'first_seen_at' => \fake()->dateTime(),
            'last_seen_at' => \fake()->dateTime(),
        ];
    }
}
