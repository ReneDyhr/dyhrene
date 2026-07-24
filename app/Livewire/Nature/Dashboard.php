<?php

declare(strict_types=1);

namespace App\Livewire\Nature;

use App\Models\DailySpeciesSummary;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        $today = \now('Europe/Copenhagen')->format('Y-m-d');

        // Species observed today, ordered by most recent
        $todaySummaries = DailySpeciesSummary::query()
            ->whereDate('date', $today)
            ->whereHas('species', function (\Illuminate\Database\Eloquent\Builder $q): void {
                $q->where('status', '!=', 'rejected');
            })
            ->with(['species', 'site'])
            ->orderBy('last_seen_at', 'desc')
            ->get();

        // For each species, fetch the most recent BirdnetDetection with audio
        $speciesWithAudio = [];

        foreach ($todaySummaries as $summary) {
            $speciesId = $summary->species_id;

            /** @var null|\App\Models\BirdnetDetection $detection */
            $detection = \App\Models\BirdnetDetection::query()
                ->where('species_id', $speciesId)
                ->whereNotNull('audio_path')
                ->where('audio_path', '!=', '')
                ->orderBy('recorded_at', 'desc')
                ->first();

            $audioUrl = $detection?->audioUrl();

            $speciesWithAudio[$speciesId] = [
                'has_audio' => $audioUrl !== null,
                'audio_url' => $audioUrl,
            ];
        }

        return \view('livewire.nature.dashboard', [
            'todaySummaries' => $todaySummaries,
            'speciesWithAudio' => $speciesWithAudio,
        ]);
    }
}
