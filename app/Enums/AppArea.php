<?php

declare(strict_types=1);

namespace App\Enums;

enum AppArea: string
{
    case Overview = 'overview';
    case Kitchen = 'kitchen';
    case Nature = 'nature';
    case Workshop = 'workshop';
    case Household = 'household';
    case Family = 'family';

    public function icon(): string
    {
        return match ($this) {
            self::Overview => '◫',
            self::Kitchen => '◒',
            self::Nature => '✦',
            self::Workshop => '◇',
            self::Household => '⌂',
            self::Family => '○',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Overview => 'Oversigt',
            self::Kitchen => 'Køkken',
            self::Nature => 'Natur',
            self::Workshop => 'Værksted',
            self::Household => 'Husholdning',
            self::Family => 'Familien',
        };
    }

    public function accentVar(): string
    {
        return match ($this) {
            self::Overview => '--neutral',
            self::Kitchen => '--koekken',
            self::Nature => '--natur',
            self::Workshop => '--vaerksted',
            self::Household => '--husholdning',
            self::Family => '--familien',
        };
    }
}
