<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryAttachment;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryAttachment>
 */
class InventoryAttachmentFactory extends Factory
{
    protected $model = InventoryAttachment::class;

    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'file_path' => 'inventory/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 10485760),
        ];
    }
}
