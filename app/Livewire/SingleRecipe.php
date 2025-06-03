<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Livewire\Component;

class SingleRecipe extends Component
{
    public int $id;

    public Recipe $recipe;

    public bool $deleteCheck = false;

    public function mount(int $id): void
    {
        $this->id = $id;
        $this->recipe = Recipe::with(['ingredients', 'tags', 'categories'])->forAuthUser()->where('id', $this->id)->firstOrFail();
    }

    public function render(): View
    {
        return \view('livewire.recipes.single', ['title' => $this->recipe->name]);
    }

    public function toggleFavourite(): void
    {
        $this->recipe->toggleFavourite();
    }

    public function delete(): RedirectResponse
    {
        if ($this->deleteCheck) {
            $this->recipe->delete();

            return \redirect()->route('index');
        }

        return \redirect()->route('single', $this->recipe->id);
    }
}
