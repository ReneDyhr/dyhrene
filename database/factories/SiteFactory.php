<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SiteTypeEnum;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => \fake()->unique()->city(),
            'latitude' => \fake()->latitude(54, 57),
            'longitude' => \fake()->longitude(8, 15),
            'type' => SiteTypeEnum::AcousticStation->value,
            'timezone' => 'Europe/Copenhagen',
            'user_id' => User::factory(),
        ];
    }
}
