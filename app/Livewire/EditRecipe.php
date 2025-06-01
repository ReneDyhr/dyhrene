<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Recipe;
use Livewire\Component;
use Livewire\Attributes\Validate; 

class EditRecipe extends Component
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


    public function mount(int $id)
    {
        $this->id = $id;
        $recipe = Recipe::forAuthUser()->where('id', $this->id)->firstOrFail();
        $this->fill($recipe->only(['name', 'description', 'note', 'public']));
        $this->ingredients = $recipe->ingredients->pluck('name')->toArray();
        $this->selectedCategories = $recipe->categories->pluck('id')->toArray();
        $this->categories = $this->selectedCategories;
        $this->tags = $recipe->tags->pluck('name')->implode(', ');
    }
    public function render()
    {
        $recipe = Recipe::with(['ingredients', 'tags', 'categories'])->forAuthUser()->where('id', $this->id)->firstOrFail();
        return view('livewire.recipes.edit', ['title' => 'Edit: '.$recipe->name, 'recipe' => $recipe]);
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
        $recipe = Recipe::forAuthUser()->where('id', $this->id)->firstOrFail();
        $recipe->update($validate);
        $recipe->categories()->sync($this->categories);
        $recipe->ingredients()->delete();
        foreach ($this->ingredients as $ingredient) {
            $recipe->ingredients()->create(['name' => $ingredient]);
        }
        $recipe->tags()->delete();
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
