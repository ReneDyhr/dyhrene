<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs;

use App\Domain\Printing\PrintJobCalculator;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public PrintJob $printJob;
    public string $date = '';
    public ?int $customer_id = null;
    public string $description = '';
    public ?string $internal_notes = null;

    /**
     * @var array<string, array<string, float>>|null
     */
    public ?array $calculation = null;

    public function mount(PrintJob $printJob): void
    {
        $this->printJob = $printJob->load(['customer', 'material', 'material.materialType']);

        // Load admin fields for editing (if locked)
        $this->date = $printJob->date->format('Y-m-d');
        $this->customer_id = $printJob->customer_id;
        $this->description = $printJob->description;
        $this->internal_notes = $printJob->internal_notes;

        // Load calculation data
        if ($printJob->isLocked()) {
            // Use snapshot for locked jobs
            $this->calculation = $printJob->calc_snapshot;
        } else {
            // Compute live for draft jobs
            $settings = PrintSetting::current();
            $calculator = new PrintJobCalculator();
            $input = $this->buildCalculatorInput($printJob, $settings);
            $this->calculation = $calculator->calculate($input);
        }
    }

    public function saveAdminFields(): void
    {
        // Only allow editing admin fields for locked jobs
        if (!$this->printJob->isLocked()) {
            \session()->flash('error', 'Admin fields can only be edited for locked jobs.');
            return;
        }

        $this->validate([
            'date' => 'required|date',
            'customer_id' => 'required|exists:print_customers,id',
            'description' => 'required|string',
            'internal_notes' => 'nullable|string',
        ]);

        $this->printJob->update([
            'date' => $this->date,
            'customer_id' => $this->customer_id,
            'description' => $this->description,
            'internal_notes' => $this->internal_notes,
        ]);

        \session()->flash('success', 'Admin fields updated successfully.');
    }

    /**
     * Placeholder for unlock method (will be implemented in Phase 6).
     */
    public function unlock(): void
    {
        // Placeholder - will be implemented in Phase 6
    }

    /**
     * Build calculator input array from print job and settings.
     *
     * @param PrintJob $job
     * @param PrintSetting $settings
     * @return array<string, mixed>
     */
    private function buildCalculatorInput(PrintJob $job, PrintSetting $settings): array
    {
        return [
            'pieces_per_plate' => $job->pieces_per_plate,
            'plates' => $job->plates,
            'grams_per_plate' => $job->grams_per_plate,
            'hours_per_plate' => $job->hours_per_plate,
            'labor_hours' => $job->labor_hours,
            'is_first_time_order' => $job->is_first_time_order,
            'avance_pct_override' => $job->avance_pct_override,
            'electricity_rate_dkk_per_kwh' => $settings->electricity_rate_dkk_per_kwh ?? 0,
            'wage_rate_dkk_per_hour' => $settings->wage_rate_dkk_per_hour ?? 0,
            'first_time_fee_dkk' => $settings->first_time_fee_dkk ?? 0,
            'default_avance_pct' => $settings->default_avance_pct ?? 0,
            'price_per_kg_dkk' => $job->material->price_per_kg_dkk ?? 0,
            'waste_factor_pct' => $job->material->waste_factor_pct ?? 0,
            'avg_kwh_per_hour' => $job->material->materialType->avg_kwh_per_hour ?? 0,
        ];
    }

    public function render(): View
    {
        $customers = PrintCustomer::query()->active()->orderBy('name')->get();

        return \view('livewire.print-jobs.show', \compact('customers'));
    }
}

