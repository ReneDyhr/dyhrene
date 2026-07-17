<?php

declare(strict_types=1);

namespace App\Livewire\Species;

use App\Models\Species;
use Livewire\Component;

class SpeciesShow extends Component
{
    public Species $species;

    public function mount(Species $species): void
    {
        \abort_if($species->user_id !== \auth()->id(), 403);
        $this->species = $species->load('observations');
    }

    public function monthlyData(): array
    {
        return $this->species->observations()
            ->selectRaw("DATE_FORMAT(observed_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    public function render()
    {
        return \view('livewire.species.species-show', [
            'observations' => $this->species->observations()
                ->orderBy('observed_at', 'desc')
                ->get(),
            'monthlyData' => $this->monthlyData(),
        ]);
    }
}
