<?php

declare(strict_types=1);

use App\Domain\WildEdibles\SeasonMatcher;

\covers(SeasonMatcher::class);

\it('matches inclusive seasons and wrapped seasons', function (): void {
    \expect(SeasonMatcher::matches(3, 6, 3))->toBeTrue()
        ->and(SeasonMatcher::matches(3, 6, 6))->toBeTrue()
        ->and(SeasonMatcher::matches(3, 6, 2))->toBeFalse()
        ->and(SeasonMatcher::matches(11, 2, 12))->toBeTrue()
        ->and(SeasonMatcher::matches(11, 2, 1))->toBeTrue()
        ->and(SeasonMatcher::matches(11, 2, 5))->toBeFalse();
});

\it('matches all year and does not match an unspecified season', function (): void {
    \expect(SeasonMatcher::matches(null, null, 7, true))->toBeTrue()
        ->and(SeasonMatcher::matches(null, null, 7, false))->toBeFalse();
});

\it('matches any selected month', function (): void {
    \expect(SeasonMatcher::matchesAny(11, 2, [4, 1]))->toBeTrue()
        ->and(SeasonMatcher::matchesAny(3, 6, [1, 2]))->toBeFalse();
});

\it('rejects invalid months', function (): void {
    \expect(fn(): bool => SeasonMatcher::matches(0, 4, 2))->toThrow(InvalidArgumentException::class);
    \expect(fn(): bool => SeasonMatcher::matchesAny(1, 3, [1, 13]))->toThrow(InvalidArgumentException::class);
});
