<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SiteTypeEnum;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        Site::query()->firstOrCreate(
            [
                'name' => 'Jels Skovvej 17',
                'user_id' => 1,
            ],
            [
                'latitude' => 55.38,
                'longitude' => 9.15,
                'type' => SiteTypeEnum::AcousticStation->value,
                'timezone' => 'Europe/Copenhagen',
            ],
        );
    }
}
