<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BirdnetDetection;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BirdnetDetection>
 */
class BirdnetDetectionFactory extends Factory
{
    protected $model = BirdnetDetection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'detection_uuid' => \fake()->uuid(),
            'scientific_name' => \fake()->unique()->word() . ' ' . \fake()->unique()->word(),
            'common_name' => \fake()->word(),
            'confidence' => \fake()->randomFloat(4, 0, 1),
            'start_time' => \fake()->randomFloat(2, 0, 60),
            'end_time' => \fake()->randomFloat(2, 0, 60),
            'recorded_at' => \fake()->dateTime(),
            'latitude' => \fake()->latitude(),
            'longitude' => \fake()->longitude(),
            'species_id' => Species::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function withEndTime(float $endTime): static
    {
        return $this->state(fn(array $attributes): array => [
            'end_time' => $attributes['start_time'] + \abs($endTime - $attributes['start_time']),
        ]);
    }
}
