<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PrintSetting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PrintSetting::updateOrCreate(
            ['id' => 1],
            [
                'electricity_rate_dkk_per_kwh' => 2.50,
                'wage_rate_dkk_per_hour' => 0.00,
                'default_avance_pct' => 50.00,
                'first_time_fee_dkk' => 0.00,
            ]
        );
    }
}
