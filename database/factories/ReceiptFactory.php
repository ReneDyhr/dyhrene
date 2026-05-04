<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'vendor' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'currency' => 'USD',
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'user_id' => User::factory(),
            'file_path' => $this->faker->optional()->lexify('receipts/??????.pdf'),
        ];
    }
}
