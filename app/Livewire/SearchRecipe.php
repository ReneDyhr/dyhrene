<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Recipe;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchRecipe extends Component
{
    #[Url(as: 'q')]
    public string $query;

    public function mount(): void {}

    public function render(): View
    {
        $recipes = Recipe::with(['ingredients', 'tags', 'categories'])
            ->where('name', 'like', '%' . $this->query . '%')
            ->forAuthUser()
            ->orderBy('id', 'DESC')
            ->get();

        return \view('livewire.recipes.index', ['title' => 'Search: ' . $this->query, 'recipes' => $recipes]);
    }
}
