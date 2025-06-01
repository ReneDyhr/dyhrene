<?php

namespace App\Livewire\Tag;

use App\Models\Category;
use App\Models\Recipe;
use App\Models\RecipeTag;
use Livewire\Component;
use Livewire\Attributes\Validate; 

class Tags extends Component
{

    public string $tag;

    public function mount(string $tag)
    {
        $this->tag = $tag;
    }
    public function render()
    {
        $recipes = Recipe::with(['ingredients', 'tags', 'categories'])->whereHas('tags', function ($query) {
            $query->where('name', $this->tag);
        })->forAuthUser()
        ->orderBy('id', 'DESC')
        ->get();
        return view('livewire.recipes.index', ['title' => 'Tag: '.$this->tag, 'recipes' => $recipes]);
    }
}
