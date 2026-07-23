<?php

declare(strict_types=1);

namespace App\Livewire\Species;

use App\Actions\DeleteObservationAction;
use App\Models\Observation;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ObservationsIndex extends Component
{
    use WithPagination;

    public string $sortField = 'observed_at';

    public string $sortDirection = 'desc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $observationId, DeleteObservationAction $action): void
    {
        $observation = Observation::query()->findOrFail($observationId);

        \abort_if($observation->user_id !== \auth()->id(), 403);

        $action->handle($observation);

        \session()->flash('success', 'Observation deleted.');
    }

    public function render(): View
    {
        $allowed = ['observed_at', 'common_name', 'count', 'source'];
        $field = \in_array($this->sortField, $allowed, true) ? $this->sortField : 'observed_at';
        $dir = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $observations = Observation::query()
            ->where('observations.user_id', \auth()->id())
            ->with(['species', 'birdnetDetections'])
            ->join('species', 'observations.species_id', '=', 'species.id')
            ->select('observations.*');

        // Sort by species common_name needs the join, other fields are on observations
        if ($field === 'common_name') {
            $observations->orderBy('species.common_name', $dir);
        } else {
            $observations->orderBy('observations.' . $field, $dir);
        }

        // Secondary sort for consistent ordering
        $observations->orderBy('observations.observed_at', 'desc')
            ->orderBy('observations.observed_time', 'desc');

        return \view('livewire.species.observations-index', [
            'observations' => $observations->paginate(50),
        ]);
    }
}
