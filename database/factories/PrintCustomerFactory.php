<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrintCustomer>
 */
class PrintCustomerFactory extends Factory
{
    protected $model = PrintCustomer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => \fake()->company(),
            'email' => \fake()->optional()->safeEmail(),
            'phone' => \fake()->optional()->phoneNumber(),
            'notes' => \fake()->optional()->text(),
        ];
    }
}
