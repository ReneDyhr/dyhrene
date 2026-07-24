<?php

declare(strict_types=1);

namespace App\Livewire\Species;

use App\Actions\DeleteObservationAction;
use App\Models\Observation;
use App\Models\Species;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SpeciesShow extends Component
{
    use WithPagination;

    public Species $species;

    public function mount(Species $species): void
    {
        \abort_if($species->user_id !== \auth()->id(), 403);
        $this->species = $species;
    }

    public function delete(int $observationId, DeleteObservationAction $action): void
    {
        $observation = $this->species->observations()->findOrFail($observationId);

        \abort_if($observation->user_id !== \auth()->id(), 403);

        $action->handle($observation);

        \session()->flash('success', 'Observation deleted.');
    }

    /**
     * @return array<string, int>
     */
    public function monthlyData(): array
    {
        /** @var array<string, string> $db */
        $db = Observation::query()
            ->where('species_id', $this->species->id)
            ->selectRaw("DATE_FORMAT(observed_at, '%m') as m, COUNT(*) as count")
            ->groupBy('m')
            ->pluck('count', 'm')
            ->toArray();

        $months = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar',
            '04' => 'Apr', '05' => 'May', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Aug', '09' => 'Sep',
            '10' => 'Oct', '11' => 'Nov', '12' => 'Dec',
        ];

        $result = [];

        foreach ($months as $num => $label) {
            $result[$label] = isset($db[$num]) ? (int) $db[$num] : 0;
        }

        return $result;
    }

    public function render(): View
    {
        return \view('livewire.species.species-show', [
            'observations' => $this->species->observations()
                ->with(['birdnetDetections'])
                ->orderBy('observed_at', 'desc')
                ->orderBy('observed_time', 'desc')
                ->paginate(50),
            'monthlyData' => $this->monthlyData(),
        ]);
    }
}
