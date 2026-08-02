<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => InventoryCategory::factory(),
            'name' => $this->faker->words(3, true),
            'brand' => $this->faker->optional()->company(),
            'model' => $this->faker->optional()->bothify('??-###'),
            'serial_number' => $this->faker->optional()->bothify('SN-########'),
            'owner' => 'shared',
            'price' => $this->faker->optional()->randomFloat(2, 10, 5000),
            'current_value' => $this->faker->optional()->randomFloat(2, 10, 5000),
            'acquisition_type' => 'bought',
            'acquisition_date' => $this->faker->date(),
            'acquired_from' => $this->faker->optional()->company(),
            'status' => 'owned',
            'status_change_date' => null,
            'status_reason' => null,
        ];
    }
}
