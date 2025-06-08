<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\ReceiptCategory;
use App\Models\ReceiptItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptItem>
 */
class ReceiptItemFactory extends Factory
{
    protected $model = ReceiptItem::class;

    public function definition(): array
    {
        return [
            'receipt_id' => Receipt::factory(),
            'name' => $this->faker->word(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'category_id' => ReceiptCategory::factory(),
        ];
    }
}
