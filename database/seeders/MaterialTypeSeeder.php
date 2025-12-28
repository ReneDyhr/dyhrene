<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PrintMaterialType;
use Illuminate\Database\Seeder;

class MaterialTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PrintMaterialType::create([
            'name' => 'PLA',
            'avg_kwh_per_hour' => 0.10,
        ]);

        PrintMaterialType::create([
            'name' => 'PETG',
            'avg_kwh_per_hour' => 0.10,
        ]);

        PrintMaterialType::create([
            'name' => 'TPU',
            'avg_kwh_per_hour' => 0.10,
        ]);
    }
}
