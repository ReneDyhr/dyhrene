<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WildEdibleTypeEnum;
use App\Models\User;
use App\Models\WildEdible;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WildEdible> */
class WildEdibleFactory extends Factory
{
    protected $model = WildEdible::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => WildEdibleTypeEnum::Other,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'location_name' => $this->faker->optional()->city(),
            'latitude' => $this->faker->latitude(54, 58),
            'longitude' => $this->faker->longitude(8, 13),
            'season_start_month' => 3,
            'season_end_month' => 9,
            'season_all_year' => false,
        ];
    }
}
