<?php

declare(strict_types=1);

namespace App\Livewire\Species;

use App\Models\Species;
use Illuminate\View\View;
use Livewire\Component;

class SpeciesShow extends Component
{
    public Species $species;

    public function mount(Species $species): void
    {
        \abort_if($species->user_id !== \auth()->id(), 403);
        $this->species = $species->load('observations');
    }

    /**
     * @return array<string, int>
     */
    public function monthlyData(): array
    {
        /** @var array<string, string> $db */
        $db = $this->species->observations()
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
                ->orderBy('observed_at', 'desc')
                ->get(),
            'monthlyData' => $this->monthlyData(),
        ]);
    }
}
