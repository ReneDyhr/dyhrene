<?php

declare(strict_types=1);

namespace App\Services\Fastmail\Support;

final class JmapCasts
{
    public static function string(mixed $value, string $default = ''): string
    {
        return \is_string($value) ? $value : $default;
    }

    public static function nullableString(mixed $value): ?string
    {
        return \is_string($value) ? $value : null;
    }

    public static function int(mixed $value, int $default = 0): int
    {
        return \is_int($value) ? $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public static function associativeArray(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            if (\is_string($key)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
    }
}
