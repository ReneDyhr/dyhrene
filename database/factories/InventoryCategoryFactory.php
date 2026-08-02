<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryCategory>
 */
class InventoryCategoryFactory extends Factory
{
    protected $model = InventoryCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'color' => $this->faker->safeHexColor(),
            'user_id' => User::factory(),
        ];
    }
}
