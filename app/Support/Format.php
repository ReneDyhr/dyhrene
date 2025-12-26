<?php

declare(strict_types=1);

namespace App\Support;

class Format
{
    /**
     * Format a number with Danish formatting (dot for thousands, comma for decimals).
     *
     * @param float|null $v The value to format
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Formatted number string
     */
    public static function number(float|null $v, int $decimals = 2): string
    {
        if ($v === null) {
            return '';
        }

        // Format with specified decimals
        $formatted = number_format((float) $v, $decimals, ',', '.');

        // Remove trailing zeros after decimal point
        if ($decimals > 0) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, ',');
        }

        return $formatted;
    }

    /**
     * Format a monetary value in Danish Krone (DKK).
     *
     * @param float|null $v The value to format
     * @return string Formatted monetary string with "kr." suffix
     */
    public static function dkk(float|null $v): string
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
     * @param float|null $v The value to format (e.g., 50 for 50%)
     * @return string Formatted percentage string with "%" suffix
     */
    public static function pct(float|null $v): string
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
     * @param int|null $v The value to format
     * @param string $suffix Optional suffix (e.g., "stk.")
     * @return string Formatted integer string with thousand separators
     */
    public static function integer(int|null $v, string $suffix = ''): string
    {
        if ($v === null) {
            return '';
        }

        $formatted = number_format((int) $v, 0, ',', '.');

        if ($suffix !== '') {
            $formatted .= ' ' . $suffix;
        }

        return $formatted;
    }
}

