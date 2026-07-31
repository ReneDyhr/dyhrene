<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryAcquisitionTypeEnum: string
{
    case Bought = 'bought';
    case Gift = 'gift';
    case Inherited = 'inherited';
    case Found = 'found';
    case Built = 'built';

    public function label(): string
    {
        return match ($this) {
            self::Bought => 'Bought',
            self::Gift => 'Gift',
            self::Inherited => 'Inherited',
            self::Found => 'Found',
            self::Built => 'Built',
        };
    }
}
