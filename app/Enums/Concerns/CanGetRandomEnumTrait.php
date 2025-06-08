<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait CanGetRandomEnumTrait
{
    /**
     * Returns a random enum
     * Used primarily for testing purposes.
     *
     * @return static
     */
    public static function random()
    {
        $values = static::cases();
        $valueCount = \count($values) - 1;

        return $values[\rand(0, $valueCount)];
    }
}
