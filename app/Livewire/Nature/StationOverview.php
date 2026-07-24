<?php

declare(strict_types=1);

namespace App\Livewire\Nature;

use App\Enums\ObservationSourceEnum;
use App\Models\DailySpeciesSummary;
use App\Models\ObservationWindow;
use Illuminate\View\View;
use Livewire\Component;

class StationOverview extends Component
{
    /** @var ?int Selected month (1-12), null = show annual calendar */
    public ?int $selectedMonth = null;

    /** @var ?int Selected year for calendar */
    public ?int $selectedYear = null;

    public function mount(): void
    {
        $this->selectedYear = (int) \now('Europe/Copenhagen')->format('Y');
    }

    public function selectMonth(int $month): void
    {
        $this->selectedMonth = $month;
    }

    public function clearMonth(): void
    {
        $this->selectedMonth = null;
    }

    /**
     * Cumulative unique species over time.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    public function accumulationData(): array
    {
        $rows = DailySpeciesSummary::query()
            ->selectRaw('date, species_id')
            ->orderBy('date')
            ->toBase()
            ->get()
            ->groupBy('date');

        $labels = [];
        $data = [];
        $seen = [];

        foreach ($rows as $date => $group) {
            foreach ($group as $row) {
                $seen[(int) $row->species_id] = true;
            }

            $labels[] = (string) $date;
            $data[] = \count($seen);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Calendar heatmap: species count per local day for the selected year.
     *
     * @return array{year: int, weeks: list<list<array{day: int, date: string, count: int, month: int}>>, months: list<string>}
     */
    public function calendarData(): array
    {
        $year = $this->selectedYear ?? (int) \now('Europe/Copenhagen')->format('Y');

        /** @var array<string, int> $daily */
        $daily = DailySpeciesSummary::query()
            ->whereYear('date', (string) $year)
            ->selectRaw('date, COUNT(DISTINCT species_id) as species_count')
            ->groupBy('date')
            ->pluck('species_count', 'date')
            ->toArray();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $weeks = [];

        // Build week grid starting from Jan 1
        $start = new \DateTimeImmutable("{$year}-01-01");
        $end = new \DateTimeImmutable("{$year}-12-31");
        $current = $start;

        // Pad to Monday
        $dayOfWeek = (int) $current->format('N');
        $current = $current->modify('-' . ($dayOfWeek - 1) . ' days');

        $week = [];

        while ($current <= $end || \count($week) % 7 !== 0) {
            $dateStr = $current->format('Y-m-d');
            $inYear = (int) $current->format('Y') === $year;

            $week[] = [
                'day' => (int) $current->format('j'),
                'date' => $dateStr,
                'count' => $inYear ? ($daily[$dateStr] ?? 0) : -1,
                'month' => (int) $current->format('n'),
            ];

            if (\count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $current = $current->modify('+1 day');
        }

        return [
            'year' => $year,
            'weeks' => $weeks,
            'months' => $months,
        ];
    }

    /**
     * Month view: species active in the selected month, ranked by windows_present.
     *
     * @return list<array{common_name: string, scientific_name: string, species_id: int, windows: int}>
     */
    public function monthViewData(): array
    {
        if ($this->selectedMonth === null) {
            return [];
        }

        $year = $this->selectedYear ?? (int) \now('Europe/Copenhagen')->format('Y');

        /** @var list<object{common_name: string, scientific_name: string, species_id: string, windows: string}> $rawRows */
        $rawRows = DailySpeciesSummary::query()
            ->whereYear('date', (string) $year)
            ->whereMonth('date', (string) $this->selectedMonth)
            ->join('species', 'daily_species_summaries.species_id', '=', 'species.id')
            ->where('species.status', '!=', 'rejected')
            ->selectRaw('species.common_name, species.scientific_name, daily_species_summaries.species_id, SUM(daily_species_summaries.windows_present) as windows')
            ->groupBy('species.common_name', 'species.scientific_name', 'daily_species_summaries.species_id')
            ->orderBy('windows', 'desc')
            ->toBase()
            ->get()
            ->toArray();

        /** @var list<array{common_name: string, scientific_name: string, species_id: int, windows: int}> $rows */
        $rows = \array_map(fn(object $r): array => [
            'common_name' => $r->common_name,
            'scientific_name' => $r->scientific_name,
            'species_id' => (int) $r->species_id,
            'windows' => (int) $r->windows,
        ], $rawRows);

        return $rows;
    }

    /**
     * Source breakdown: acoustic vs manual windows.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    public function sourceBreakdown(): array
    {
        $totalWindows = ObservationWindow::query()->count();

        if ($totalWindows === 0) {
            return ['labels' => ['No data'], 'data' => [1]];
        }

        $birdnet = ObservationWindow::query()
            ->where('source', ObservationSourceEnum::Birdnet->value)
            ->count();

        $ebird = ObservationWindow::query()
            ->where('source', ObservationSourceEnum::EbirdImport->value)
            ->count();

        $manual = ObservationWindow::query()
            ->where('source', ObservationSourceEnum::Manual->value)
            ->count();

        return [
            'labels' => ['BirdNET', 'eBird', 'Manual'],
            'data' => [$birdnet, $ebird, $manual],
        ];
    }

    public function render(): View
    {
        return \view('livewire.nature.station-overview', [
            'accumulationData' => $this->accumulationData(),
            'calendarData' => $this->calendarData(),
            'monthViewData' => $this->monthViewData(),
            'sourceBreakdown' => $this->sourceBreakdown(),
        ]);
    }
}
