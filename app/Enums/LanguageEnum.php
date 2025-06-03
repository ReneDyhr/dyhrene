<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\CanGetRandomEnumTrait;

enum LanguageEnum: string
{
    use CanGetRandomEnumTrait;

    case DANISH = 'DA';
    case ENGLISH = 'EN';
    case GERMAN = 'DE';
}
