<?php

declare(strict_types=1);

namespace App\Domain\Overview;

final readonly class RecipeOverview
{
    public function __construct(
        public int $count,
        public ?LatestRecipe $latest,
    ) {}
}
