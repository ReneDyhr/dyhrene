<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReceiptCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptCategory>
 */
class ReceiptCategoryFactory extends Factory
{
    protected $model = ReceiptCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'color' => $this->faker->safeHexColor(),
            'user_id' => User::factory(),
        ];
    }
}
