<?php

namespace App\Livewire\Category;

use App\Models\Category;
use App\Models\Recipe;
use Livewire\Component;
use Livewire\Attributes\Validate; 

class Categories extends Component
{
    public Category $category;

    public function mount(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $this->category = $category;
    }
    public function render()
    {
        $recipes = Recipe::with(['ingredients', 'tags', 'categories'])
            ->whereHas('categories', function ($query) {
                $query->where('categories.id', $this->category->id);
            })
            ->forAuthUser()
            ->orderBy('id', 'DESC')
            ->get();
        return view('livewire.recipes.index', ['title' => 'Category: '.$this->category->name, 'recipes' => $recipes]);
    }
}
