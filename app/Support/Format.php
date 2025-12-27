<?php

declare(strict_types=1);

namespace App\Support;

class Format
{
    /**
     * Format a number with Danish formatting (dot for thousands, comma for decimals).
     *
     * @param  null|float $v        The value to format
     * @param  int        $decimals Number of decimal places (default: 2)
     * @return string     Formatted number string
     */
    public static function number(?float $v, int $decimals = 2): string
    {
        if ($v === null) {
            return '';
        }

        // Format with specified decimals
        $formatted = \number_format($v, $decimals, ',', '.');

        // Remove trailing zeros after decimal point
        if ($decimals > 0) {
            $formatted = \rtrim($formatted, '0');
            $formatted = \rtrim($formatted, ',');
        }

        return $formatted;
    }

    /**
     * Format a monetary value in Danish Krone (DKK).
     *
     * @param  null|float $v The value to format
     * @return string     Formatted monetary string with "kr." suffix
     */
    public static function dkk(?float $v): string
    {
        if ($v === null) {
            return '';
        }

        $formatted = self::number($v, 2);

        if ($formatted === '') {
            return '';
        }

        return $formatted . ' kr.';
    }

    /**
     * Format a percentage value.
     *
     * @param  null|float $v The value to format (e.g., 50 for 50%)
     * @return string     Formatted percentage string with "%" suffix
     */
    public static function pct(?float $v): string
    {
        if ($v === null) {
            return '';
        }

        $formatted = self::number($v, 2);

        if ($formatted === '') {
            return '';
        }

        return $formatted . ' %';
    }

    /**
     * Format an integer with thousand separators.
     *
     * @param  null|int $v      The value to format
     * @param  string   $suffix Optional suffix (e.g., "stk.")
     * @return string   Formatted integer string with thousand separators
     */
    public static function integer(?int $v, string $suffix = ''): string
    {
        if ($v === null) {
            return '';
        }

        $formatted = \number_format($v, 0, ',', '.');

        if ($suffix !== '') {
            $formatted .= ' ' . $suffix;
        }

        return $formatted;
    }
}
