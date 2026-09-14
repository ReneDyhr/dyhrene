<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Picking;
use App\Models\WildEdible;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Picking> */
class PickingFactory extends Factory
{
    protected $model = Picking::class;

    public function definition(): array
    {
        return [
            'wild_edible_id' => WildEdible::factory(),
            'user_id' => function (array $attributes): int {
                return WildEdible::query()->findOrFail((int) $attributes['wild_edible_id'])->user_id;
            },
            'picked_at' => $this->faker->date(),
            'latitude' => $this->faker->latitude(54, 58),
            'longitude' => $this->faker->longitude(8, 13),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
