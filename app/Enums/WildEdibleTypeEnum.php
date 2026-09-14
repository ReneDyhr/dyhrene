<?php

declare(strict_types=1);

namespace App\Enums;

enum WildEdibleTypeEnum: string
{
    case Mushroom = 'mushroom';
    case Berry = 'berry';
    case Fruit = 'fruit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mushroom => 'Mushroom',
            self::Berry => 'Berry',
            self::Fruit => 'Fruit',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Mushroom => '#795548',
            self::Berry => '#9C27B0',
            self::Fruit => '#FF9800',
            self::Other => '#53875F',
        };
    }

    public function markerIcon(): string
    {
        return match ($this) {
            self::Mushroom => '🍄',
            self::Berry => '🫐',
            self::Fruit => '🍎',
            self::Other => '🌿',
        };
    }
}
