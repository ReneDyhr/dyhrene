<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Recipe;
use Illuminate\View\View;
use Livewire\Component;

class Recipes extends Component
{
    public function render(): View
    {
        $recipes = Recipe::with(['ingredients', 'tags', 'categories'])->forAuthUser()->orderBy('id', 'DESC')->get();

        return \view('livewire.recipes.index', ['title' => 'Home', 'recipes' => $recipes]);
    }
}
