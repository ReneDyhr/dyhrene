<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintOrderSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrintOrderSequence>
 */
class PrintOrderSequenceFactory extends Factory
{
    protected $model = PrintOrderSequence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => \fake()->numberBetween(2020, 2030),
            'last_number' => \fake()->numberBetween(0, 1000),
        ];
    }
}
