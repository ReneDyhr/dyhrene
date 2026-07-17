<?php

declare(strict_types=1);

namespace App\Livewire\Species;

use App\Models\Observation;
use App\Models\Species;
use Livewire\Component;

class AddObservation extends Component
{
    public string $speciesSearch = '';

    public array $speciesResults = [];

    public ?int $selectedSpeciesId = null;

    public string $date = '';

    public string $time = '';

    public string $count = '';

    public string $location = '';

    public function mount(?Species $species = null): void
    {
        $this->date = now()->format('Y-m-d');

        if ($species !== null && $species->user_id === auth()->id()) {
            $this->selectedSpeciesId = $species->id;
            $this->speciesSearch = $species->common_name;
        }
    }

    public function updatedSpeciesSearch(): void
    {
        if (\mb_strlen($this->speciesSearch) >= 2) {
            $this->speciesResults = Species::query()
                ->where('user_id', \auth()->id())
                ->where(function ($q): void {
                    $q->where('common_name', 'like', '%' . $this->speciesSearch . '%')
                        ->orWhere('scientific_name', 'like', '%' . $this->speciesSearch . '%');
                })
                ->orderBy('common_name')
                ->limit(10)
                ->get()
                ->toArray();
        } else {
            $this->speciesResults = [];
        }
    }

    public function selectSpecies(int $id): void
    {
        $this->selectedSpeciesId = $id;
        $species = Species::find($id);
        $this->speciesSearch = $species?->common_name ?? '';
        $this->speciesResults = [];
    }

    public function createSpecies(): void
    {
        $species = Species::create([
            'common_name' => \trim($this->speciesSearch),
            'scientific_name' => '',
            'user_id' => \auth()->id(),
        ]);
        $this->selectedSpeciesId = $species->id;
        $this->speciesResults = [];
    }

    public function save(): void
    {
        $this->validate([
            'selectedSpeciesId' => 'required|exists:species,id',
            'date' => 'required|date',
        ]);

        Observation::create([
            'species_id' => $this->selectedSpeciesId,
            'user_id' => \auth()->id(),
            'observed_at' => $this->date,
            'observed_time' => $this->time ?: null,
            'count' => $this->count ?: 'X',
            'location' => $this->location ?: null,
            'source' => 'manual',
        ]);

        session()->flash('success', 'Observation logged!');
        $this->redirect(route('species.show', $this->selectedSpeciesId));
    }

    public function render()
    {
        return \view('livewire.species.add-observation');
    }
}
