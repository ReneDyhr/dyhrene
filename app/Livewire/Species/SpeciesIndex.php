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

    public string $sortField = 'taxonomic_order';

    public string $sortDirection = 'asc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
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

        // Sortable columns
        $allowed = ['common_name', 'scientific_name', 'observations_count', 'taxonomic_order'];
        $field = \in_array($this->sortField, $allowed, true) ? $this->sortField : 'taxonomic_order';
        $dir = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return \view('livewire.species.species-index', [
            'speciesList' => $query->orderBy($field, $dir)->paginate(25),
        ]);
    }
}
