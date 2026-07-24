<?php

declare(strict_types=1);

namespace App\Enums;

enum SpeciesStatusEnum: string
{
    case Expected = 'expected';
    case Unusual = 'unusual';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Expected => 'Expected',
            self::Unusual => 'Unusual',
            self::Rejected => 'Rejected',
        };
    }

    public function isVisible(): bool
    {
        return $this !== self::Rejected;
    }
}
