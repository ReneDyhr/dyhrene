<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Search\RecipeSearch;
use App\Domain\Search\SearchQuery;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchRecipe extends Component
{
    #[Url(as: 'q')]
    public string $query;

    public function render(RecipeSearch $search): View
    {
        $user = \auth()->user();

        $recipes = $user instanceof User
            ? $search->searchFor($user, new SearchQuery($this->query))
            : [];

        return \view('livewire.recipes.index', ['title' => 'Search: ' . $this->query, 'recipes' => $recipes]);
    }
}
