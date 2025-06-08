<?php

declare(strict_types=1);

namespace App\Livewire\Category;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;

class Categories extends Component
{
    public Category $category;

    public function mount(string $slug): void
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $this->category = $category;
    }

    public function render(): View
    {
        $recipes = Recipe::with(['ingredients', 'tags', 'categories'])
            ->whereHas('categories', function (Builder $query): void {
                $query->where('categories.id', $this->category->id);
            })
            ->forAuthUser()
            ->orderBy('id', 'DESC')
            ->get();

        return \view('livewire.recipes.index', ['title' => 'Category: ' . $this->category->name, 'recipes' => $recipes]);
    }
}
