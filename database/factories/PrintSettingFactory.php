<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrintSetting>
 */
class PrintSettingFactory extends Factory
{
    protected $model = PrintSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'electricity_rate_dkk_per_kwh' => \fake()->randomFloat(4, 1.0, 5.0),
            'wage_rate_dkk_per_hour' => \fake()->randomFloat(2, 100, 500),
            'default_avance_pct' => \fake()->randomFloat(2, 20, 100),
            'first_time_fee_dkk' => \fake()->randomFloat(2, 0, 500),
        ];
    }
}

