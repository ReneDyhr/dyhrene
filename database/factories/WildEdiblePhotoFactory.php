<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WildEdible;
use App\Models\WildEdiblePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WildEdiblePhoto> */
class WildEdiblePhotoFactory extends Factory
{
    protected $model = WildEdiblePhoto::class;

    public function definition(): array
    {
        return [
            'wild_edible_id' => WildEdible::factory(),
            'storage_path' => 'wild-edibles/' . $this->faker->uuid() . '.jpg',
            'original_file_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'metadata' => ['width' => 100, 'height' => 100],
        ];
    }
}
