<?php

declare(strict_types=1);

namespace App\Domain\WildEdibles;

final class SeasonMatcher
{
    public static function matches(?int $startMonth, ?int $endMonth, int $month, bool $allYear = false): bool
    {
        self::validateMonth($month);

        if ($allYear) {
            return true;
        }

        if ($startMonth === null || $endMonth === null) {
            return false;
        }

        self::validateMonth($startMonth);
        self::validateMonth($endMonth);

        if ($startMonth <= $endMonth) {
            return $month >= $startMonth && $month <= $endMonth;
        }

        return $month >= $startMonth || $month <= $endMonth;
    }

    /** @param list<int> $months */
    public static function matchesAny(?int $startMonth, ?int $endMonth, array $months, bool $allYear = false): bool
    {
        foreach ($months as $month) {
            self::validateMonth($month);
        }

        foreach ($months as $month) {
            if ($allYear || self::matches($startMonth, $endMonth, $month)) {
                return true;
            }
        }

        return false;
    }

    private static function validateMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Month must be between 1 and 12.');
        }
    }
}
