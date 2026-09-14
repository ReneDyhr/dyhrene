<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Models\Recipe;
use App\Models\User;

final class RecipeSearch
{
    /**
     * Search the user's recipes by name. Owns normalization, ownership
     * scoping, ordering, and empty-query behaviour (empty query -> no results).
     *
     * @return list<Recipe>
     */
    public function searchFor(User $actor, SearchQuery $query): array
    {
        if ($query->isEmpty()) {
            return [];
        }

        return \array_values(
            Recipe::query()
                ->with(['ingredients', 'tags', 'categories'])
                ->where('user_id', $actor->id)
                ->where('name', 'like', '%' . $query->normalized() . '%')
                ->orderByDesc('id')
                ->get()
                ->all(),
        );
    }
}
