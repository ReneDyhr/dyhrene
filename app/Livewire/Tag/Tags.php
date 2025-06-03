<?php

declare(strict_types=1);

namespace App\Livewire\Tag;

use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;

class Tags extends Component
{
    public string $tag;

    public function mount(string $tag): void
    {
        $this->tag = $tag;
    }

    public function render(): View
    {
        $recipes = Recipe::with(['ingredients', 'tags', 'categories'])->whereHas('tags', function (Builder $query): void {
            $query->where('name', $this->tag);
        })->forAuthUser()
            ->orderBy('id', 'DESC')
            ->get();

        return \view('livewire.recipes.index', ['title' => 'Tag: ' . $this->tag, 'recipes' => $recipes]);
    }
}
