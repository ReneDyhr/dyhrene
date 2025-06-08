<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Livewire\Component;

class AddRecipe extends Component
{
    public int $id;

    public string $name = '';

    public string $description = '';

    public string $note = '';

    /**
     * @var list<int>
     */
    public array $categories = [];

    /**
     * @var array<int, string>
     */
    public array $ingredients = [];

    public string $tags = '';

    public string $newIngredient = '';

    /**
     * @var list<int>
     */
    public array $selectedCategories = [];

    public bool $public = false;

    public function render(): View
    {
        return \view('livewire.recipes.add', ['title' => 'Add Recipe']);
    }

    public function save(): RedirectResponse
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
        $recipe = Recipe::create([...$validate, 'user_id' => \auth()->id()]);
        $recipe->categories()->sync($this->categories);

        foreach ($this->ingredients as $ingredient) {
            $recipe->ingredients()->create(['name' => $ingredient]);
        }

        foreach (\explode(',', $this->tags) as $tag) {
            $recipe->tags()->create(['name' => \trim($tag)]);
        }

        return \redirect()->route('single', ['id' => $recipe->id]);
    }

    public function addIngredient(): void
    {
        if (!empty($this->newIngredient)) {
            $this->ingredients[] = $this->newIngredient;
            $this->newIngredient = ''; // Reset input
        }
    }

    public function removeIngredient(int $index): void
    {
        unset($this->ingredients[$index]);
        $this->ingredients = \array_values($this->ingredients); // Reindex the array
    }
}
