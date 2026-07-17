<?php

declare(strict_types=1);

namespace App\Livewire\Species;

use App\Models\Species;
use Livewire\Component;
use Livewire\WithPagination;

class SpeciesIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Species::query()
            ->where('user_id', \auth()->id())
            ->withCount('observations');

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('common_name', 'like', '%' . $this->search . '%')
                    ->orWhere('scientific_name', 'like', '%' . $this->search . '%');
            });
        }

        return \view('livewire.species.species-index', [
            'speciesList' => $query->orderBy('taxonomic_order')->paginate(25),
        ]);
    }
}
