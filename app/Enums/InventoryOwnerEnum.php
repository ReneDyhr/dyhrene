<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryOwnerEnum: string
{
    case Shared = 'shared';
    case Rene = 'rene';
    case Jeanette = 'jeanette';

    public function label(): string
    {
        return match ($this) {
            self::Shared => 'Shared',
            self::Rene => 'Rene',
            self::Jeanette => 'Jeanette',
        };
    }
}
