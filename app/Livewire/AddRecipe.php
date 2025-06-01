<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Recipe;
use Livewire\Component;
use Livewire\Attributes\Validate; 

class AddRecipe extends Component
{
    public int $id;
    public string $name = '';
    public string $description = '';
    public string $note = '';
    public array $categories = [];
    public array $ingredients = [];
    public string $tags = '';
    
    public string $newIngredient = '';
    public array $selectedCategories = [];

    public bool $public = false;


    public function render()
    {
        return view('livewire.recipes.add', ['title' => 'Add Recipe']);
    }

    public function save()
    {
        $validate = $this->validate([
            'name' => 'required|string',
            'description' => 'required',
            'categories' => 'required|array|min:1',
            'ingredients' => 'required|array|min:1',
            'note' => 'string',
            'public' => 'boolean',
            'tags' => 'required',
        ]);
        $recipe = Recipe::create([...$validate, 'user_id' => auth()->id()]);
        $recipe->categories()->sync($this->categories);
        foreach ($this->ingredients as $ingredient) {
            $recipe->ingredients()->create(['name' => $ingredient]);
        }
        foreach (explode(',', $this->tags) as $tag) {
            $recipe->tags()->create(['name' => trim($tag)]);
        }

        return redirect()->route('single', ['id' => $recipe->id]);
    }

    public function addIngredient()
    {
        if (!empty($this->newIngredient)) {
            $this->ingredients[] = $this->newIngredient;
            $this->newIngredient = ''; // Reset input
        }
    }

    public function removeIngredient(int $index)
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients); // Reindex the array
    }
}
