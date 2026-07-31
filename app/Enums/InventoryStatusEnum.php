<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryStatusEnum: string
{
    case Owned = 'owned';
    case Sold = 'sold';
    case Stolen = 'stolen';
    case Lost = 'lost';
    case Donated = 'donated';
    case LentOut = 'lent_out';
    case InRepair = 'in_repair';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'Owned',
            self::Sold => 'Sold',
            self::Stolen => 'Stolen',
            self::Lost => 'Lost',
            self::Donated => 'Donated',
            self::LentOut => 'Lent out',
            self::InRepair => 'In repair',
        };
    }
}
