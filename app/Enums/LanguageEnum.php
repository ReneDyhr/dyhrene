<?php

namespace App\Enums;

use \App\Enums\Concerns\CanGetRandomEnumTrait;

enum LanguageEnum: string
{
    use CanGetRandomEnumTrait;
    case DANISH = 'DA';
    case ENGLISH = 'EN';
    case GERMAN = 'DE';
}