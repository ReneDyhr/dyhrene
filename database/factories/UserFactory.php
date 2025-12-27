<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LanguageEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => \fake()->name(),
            'email' => \fake()->unique()->safeEmail(),
            'email_verified_at' => \now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'birthdate' => \fake()->date(),
            'ip' => \fake()->ipv4(),
            'language' => LanguageEnum::random(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(): array => [
            'email_verified_at' => null,
        ]);
    }
}
