<?php

declare(strict_types=1);

namespace App\Enums;

enum ObservationSourceEnum: string
{
    case Birdnet = 'birdnet';
    case EbirdImport = 'ebird_import';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Birdnet => 'BirdNET',
            self::EbirdImport => 'eBird',
            self::Manual => 'Manual',
        };
    }
}
