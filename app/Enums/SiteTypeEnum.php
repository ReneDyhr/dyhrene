<?php

declare(strict_types=1);

namespace App\Enums;

enum SiteTypeEnum: string
{
    case AcousticStation = 'acoustic_station';
    case FieldSite = 'field_site';

    public function label(): string
    {
        return match ($this) {
            self::AcousticStation => 'Acoustic Station',
            self::FieldSite => 'Field Site',
        };
    }
}
