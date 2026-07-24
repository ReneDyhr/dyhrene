<?php

declare(strict_types=1);

namespace App\Livewire\Species;

use App\Actions\DeleteObservationAction;
use App\Enums\ObservationSourceEnum;
use App\Models\DailySpeciesSummary;
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
     * Phenology data: windows-present by day-of-year, one line per year,
     * with 7-day rolling mean computed server-side.
     *
     * @return array{days: list<int>, series: list<array{label: string, data: list<float>}>}
     */
    public function phenologyData(): array
    {
        /** @var list<object{year: string, day: string, windows: string}> $rawRows */
        $rawRows = DailySpeciesSummary::query()
            ->where('species_id', $this->species->id)
            ->selectRaw('YEAR(date) as year, DAYOFYEAR(date) as day, SUM(windows_present) as windows')
            ->groupBy('year', 'day')
            ->orderBy('year')
            ->orderBy('day')
            ->toBase()
            ->get()
            ->toArray();

        /** @var list<array{year: int, day: int, windows: int}> $rows */
        $rows = \array_map(fn(object $r): array => [
            'year' => (int) $r->year,
            'day' => (int) $r->day,
            'windows' => (int) $r->windows,
        ], $rawRows);

        if ($rows === []) {
            return ['days' => [], 'series' => []];
        }

        // Build raw data per day-of-year, keyed by year
        /** @var array<int, array<int, int>> $rawByYear */
        $rawByYear = [];

        foreach ($rows as $row) {
            $rawByYear[$row['year']][$row['day']] = $row['windows'];
        }

        $minDay = 1;
        $maxDay = 366;

        // Compute 7-day rolling mean per year, padded to full day range
        $series = [];

        foreach ($rawByYear as $year => $dayData) {
            $smoothed = [];

            for ($d = $minDay; $d <= $maxDay; $d++) {
                $sum = 0;
                $count = 0;

                for ($i = $d - 3; $i <= $d + 3; $i++) {
                    $window = $i < 1 || $i > 366 ? 0 : ($dayData[$i] ?? 0);
                    $sum += $window;
                    $count++;
                }

                $smoothed[] = $count > 0 ? \round($sum / $count, 1) : 0.0;
            }

            $series[] = [
                'label' => (string) $year,
                'data' => $smoothed,
            ];
        }

        $days = \range($minDay, $maxDay);

        return [
            'days' => $days,
            'series' => $series,
        ];
    }

    /**
     * Diel activity heatmap data: raw record counts binned by
     * minutes-from-sunrise slot × month, for BirdNET observations only.
     *
     * @return array<string, mixed>
     */
    public function dielHeatmapData(): array
    {
        // Bin minutes-from-sunrise into slots: -120..-90, -90..-60, ..., 150..180, 180..210, etc.
        // We'll use 30-minute bins from -2h to +12h from sunrise
        $binSize = 30;
        $minMinutes = -120;
        $maxMinutes = 720;

        /** @var list<object{month: string, slot: string, count: string}> $rawRows */
        $rawRows = Observation::query()
            ->where('species_id', $this->species->id)
            ->where('source', ObservationSourceEnum::Birdnet->value)
            ->whereNotNull('minutes_from_sunrise')
            ->selectRaw('MONTH(local_date) as month, FLOOR((minutes_from_sunrise - ' . $minMinutes . ') / ' . $binSize . ') as slot, COUNT(*) as count')
            ->groupBy('month', 'slot')
            ->orderBy('month')
            ->orderBy('slot')
            ->toBase()
            ->get()
            ->toArray();

        /** @var list<array{month: int, slot: int, count: int}> $rows */
        $rows = \array_map(fn(object $r): array => [
            'month' => (int) $r->month,
            'slot' => (int) $r->slot,
            'count' => (int) $r->count,
        ], $rawRows);

        $slotCount = ($maxMinutes - $minMinutes) / $binSize;

        // Build heatmap grid (12 months × N slots)
        $data = [];

        for ($m = 1; $m <= 12; $m++) {
            $row = \array_fill(0, $slotCount, 0);
            $data[] = $row;
        }

        foreach ($rows as $r) {
            $mi = $r['month'] - 1;
            $si = $r['slot'];

            if ($mi >= 0 && $mi < 12 && $si >= 0 && $si < $slotCount) {
                $data[$mi][$si] = $r['count'];
            }
        }

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $slotLabels = [];

        for ($i = 0; $i < $slotCount; $i++) {
            $minutes = $minMinutes + ($i * $binSize);
            $hours = \intdiv($minutes, 60);

            if ($hours < 0) {
                $slotLabels[] = (string) $hours;
            } elseif ($hours === 0) {
                $slotLabels[] = 'S';
            } else {
                $slotLabels[] = '+' . $hours;
            }
        }

        return [
            'months' => $months,
            'slots' => $slotLabels,
            'data' => $data,
        ];
    }

    /**
     * First and last observation per year, plus days-present count.
     *
     * @return list<array{year: int, first: string, last: string, days: int}>
     */
    public function firstLastPerYear(): array
    {
        /** @var list<object{year: string, first: string, last: string, days: string}> $rawRows */
        $rawRows = DailySpeciesSummary::query()
            ->where('species_id', $this->species->id)
            ->selectRaw('YEAR(date) as year, MIN(date) as first, MAX(date) as last, COUNT(DISTINCT date) as days')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->toBase()
            ->get()
            ->toArray();

        /** @var list<array{year: int, first: string, last: string, days: int}> $rows */
        $rows = \array_map(fn(object $r): array => [
            'year' => (int) $r->year,
            'first' => $r->first,
            'last' => $r->last,
            'days' => (int) $r->days,
        ], $rawRows);

        return $rows;
    }

    /**
     * Confidence distribution histogram: BirdNET rows only.
     *
     * @return array<string, mixed>
     */
    public function confidenceData(): array
    {
        /** @var list<object{bin: string, count: string}> $rawRows */
        $rawRows = Observation::query()
            ->where('observations.species_id', $this->species->id)
            ->where('observations.source', ObservationSourceEnum::Birdnet->value)
            ->join('birdnet_detections', 'observations.id', '=', 'birdnet_detections.observation_id')
            ->selectRaw('FLOOR(birdnet_detections.confidence * 20) as bin, COUNT(*) as count')
            ->groupBy('bin')
            ->orderBy('bin')
            ->toBase()
            ->get()
            ->toArray();

        /** @var list<array{bin: int, count: int}> $rows */
        $rows = \array_map(fn(object $r): array => [
            'bin' => (int) $r->bin,
            'count' => (int) $r->count,
        ], $rawRows);

        $bins = [];
        $counts = [];

        for ($i = 0; $i <= 20; $i++) {
            $low = \round($i * 5, 0);
            $bins[] = "{$low}%";
            $counts[] = 0;
        }

        foreach ($rows as $r) {
            if ($r['bin'] >= 0 && $r['bin'] <= 20) {
                $counts[$r['bin']] = $r['count'];
            }
        }

        return [
            'bins' => $bins,
            'counts' => $counts,
        ];
    }

    public function render(): View
    {
        return \view('livewire.species.species-show', [
            'observations' => $this->species->observations()
                ->with(['birdnetDetections'])
                ->orderBy('observed_at', 'desc')
                ->orderBy('observed_time', 'desc')
                ->paginate(50),
            'phenologyData' => $this->phenologyData(),
            'dielData' => $this->dielHeatmapData(),
            'firstLast' => $this->firstLastPerYear(),
            'confidenceData' => $this->confidenceData(),
        ]);
    }
}
