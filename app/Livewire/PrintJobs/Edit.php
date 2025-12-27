<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs;

use App\Domain\Printing\PrintJobCalculator;
use App\Models\PrintActivityLog;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\PrintSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Edit extends Component
{
    public PrintJob $printJob;

    public string $date = '';

    public string $description = '';

    public ?string $internal_notes = null;

    public ?int $customer_id = null;

    public ?int $material_id = null;

    public int $pieces_per_plate = 1;

    public int $plates = 1;

    public float $grams_per_plate = 0;

    public float $hours_per_plate = 0;

    public int $hours_per_plate_hours = 0;

    public int $hours_per_plate_minutes = 0;

    public float $labor_hours = 0;

    public bool $is_first_time_order = false;

    public ?float $avance_pct_override = null;

    public function mount(PrintJob $printJob): ?Redirector
    {
        $this->printJob = $printJob;

        // Guard: if locked, redirect to show
        if ($printJob->isLocked()) {
            \session()->flash('error', 'Locked jobs cannot be edited. Please unlock first.');

            return $this->redirect(\route('print-jobs.show', $printJob));
        }

        // Load existing data
        $this->date = $printJob->date->format('Y-m-d');
        $this->description = $printJob->description;
        $this->internal_notes = $printJob->internal_notes;
        $this->customer_id = $printJob->customer_id;
        $this->material_id = $printJob->material_id;
        $this->pieces_per_plate = $printJob->pieces_per_plate;
        $this->plates = $printJob->plates;
        $this->grams_per_plate = $printJob->grams_per_plate;
        $this->hours_per_plate = $printJob->hours_per_plate;
        $this->labor_hours = $printJob->labor_hours;
        $this->is_first_time_order = $printJob->is_first_time_order;
        $this->avance_pct_override = $printJob->avance_pct_override;

        // Convert float to hours and minutes
        $this->updateTimeFromHoursPerPlate();

        return null;
    }

    /**
     * Convert hours and minutes to float hours_per_plate.
     */
    public function updatedHoursPerPlateHours(): void
    {
        $this->updateHoursPerPlateFromTime();
    }

    /**
     * Convert hours and minutes to float hours_per_plate.
     */
    public function updatedHoursPerPlateMinutes(): void
    {
        $this->updateHoursPerPlateFromTime();
    }

    public function save(): Redirector
    {
        // Guard: if locked, redirect to show
        $this->printJob->refresh();

        if ($this->printJob->isLocked()) {
            \session()->flash('error', 'Locked jobs cannot be edited. Please unlock first.');

            return $this->redirect(\route('print-jobs.show', $this->printJob));
        }

        // Ensure hours_per_plate is updated from time inputs before validation
        $this->updateHoursPerPlateFromTime();

        $this->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'internal_notes' => 'nullable|string',
            'customer_id' => 'required|exists:print_customers,id',
            'material_id' => 'required|exists:print_materials,id',
            'pieces_per_plate' => 'required|integer|min:1|max:100',
            'plates' => 'required|integer|min:1|max:10',
            'grams_per_plate' => 'required|numeric|min:0|max:999',
            'hours_per_plate_hours' => 'required|integer|min:0|max:999',
            'hours_per_plate_minutes' => 'required|integer|min:0|max:59',
            'hours_per_plate' => 'required|numeric|min:0|max:999',
            'labor_hours' => 'required|numeric|min:0|max:999',
            'is_first_time_order' => 'boolean',
            'avance_pct_override' => 'nullable|numeric|min:0|max:1000',
        ]);

        // Update draft fields
        $this->printJob->update([
            'date' => $this->date,
            'description' => $this->description,
            'internal_notes' => $this->internal_notes,
            'customer_id' => $this->customer_id,
            'material_id' => $this->material_id,
            'pieces_per_plate' => $this->pieces_per_plate,
            'plates' => $this->plates,
            'grams_per_plate' => $this->grams_per_plate,
            'hours_per_plate' => $this->hours_per_plate,
            'labor_hours' => $this->labor_hours,
            'is_first_time_order' => $this->is_first_time_order,
            'avance_pct_override' => $this->avance_pct_override,
        ]);

        \session()->flash('success', 'Print job updated successfully.');

        return $this->redirect(\route('print-jobs.show', $this->printJob));
    }

    /**
     * Lock the print job by creating a snapshot and updating status.
     */
    public function lock(): ?Redirector
    {
        // Guard: if already locked, redirect to show
        $this->printJob->refresh();

        if ($this->printJob->isLocked()) {
            \session()->flash('error', 'This job is already locked.');

            return $this->redirect(\route('print-jobs.show', $this->printJob));
        }

        // Ensure hours_per_plate is updated from time inputs before validation
        $this->updateHoursPerPlateFromTime();

        // Validate all required fields
        $this->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'customer_id' => 'required|exists:print_customers,id',
            'material_id' => 'required|exists:print_materials,id',
            'pieces_per_plate' => 'required|integer|min:1|max:100',
            'plates' => 'required|integer|min:1|max:10',
            'grams_per_plate' => 'required|numeric|min:0|max:999',
            'hours_per_plate_hours' => 'required|integer|min:0|max:999',
            'hours_per_plate_minutes' => 'required|integer|min:0|max:59',
            'hours_per_plate' => 'required|numeric|min:0|max:999',
            'labor_hours' => 'required|numeric|min:0|max:999',
            'is_first_time_order' => 'boolean',
            'avance_pct_override' => 'nullable|numeric|min:0|max:1000',
        ]);

        // First, save any pending changes
        $this->printJob->update([
            'date' => $this->date,
            'description' => $this->description,
            'internal_notes' => $this->internal_notes,
            'customer_id' => $this->customer_id,
            'material_id' => $this->material_id,
            'pieces_per_plate' => $this->pieces_per_plate,
            'plates' => $this->plates,
            'grams_per_plate' => $this->grams_per_plate,
            'hours_per_plate' => $this->hours_per_plate,
            'labor_hours' => $this->labor_hours,
            'is_first_time_order' => $this->is_first_time_order,
            'avance_pct_override' => $this->avance_pct_override,
        ]);

        // Wrap in database transaction
        DB::transaction(function (): void {
            // Refresh to get latest data
            $this->printJob->refresh();
            $this->printJob->loadMissing(['material', 'material.materialType']);

            // Build snapshot using snapshot builder
            $snapshot = $this->printJob->buildSnapshot();

            // Update job: status='locked', locked_at=now(), calc_snapshot=<snapshot json>
            $this->printJob->update([
                'status' => 'locked',
                'locked_at' => \now(),
                'calc_snapshot' => $snapshot,
            ]);

            // Log activity: create ActivityLog entry with action='locked'
            PrintActivityLog::create([
                'print_job_id' => $this->printJob->id,
                'action' => 'locked',
                'user_id' => \auth()->id(),
                'metadata' => null,
            ]);
        });

        // Refresh to ensure we have the latest data
        $this->printJob->refresh();

        \session()->flash('success', 'Print job locked successfully.');

        return $this->redirect(\route('print-jobs.show', $this->printJob));
    }

    public function render(): View
    {
        $customers = PrintCustomer::query()->active()->orderBy('name')->get();
        $materials = PrintMaterial::query()
            ->active()
            ->with('materialType')
            ->orderBy('name')
            ->get()
            ->groupBy('material_type_id');

        $materialTypes = PrintMaterialType::query()->orderBy('name')->get();

        // Compute calculation directly in render to ensure it's always up to date
        $calculation = $this->computeCalculation();

        return \view('livewire.print-jobs.edit', \compact('customers', 'materials', 'materialTypes', 'calculation'));
    }

    /**
     * Update hours_per_plate float from hours and minutes inputs.
     */
    private function updateHoursPerPlateFromTime(): void
    {
        $hours = $this->hours_per_plate_hours ?? 0;
        $minutes = $this->hours_per_plate_minutes ?? 0;

        // Ensure minutes are between 0 and 59
        if ($minutes < 0) {
            $minutes = 0;
        } elseif ($minutes > 59) {
            $minutes = 59;
        }

        // Convert to float: hours + (minutes / 60)
        $this->hours_per_plate = $hours + ($minutes / 60.0);
    }

    /**
     * Update hours and minutes from float hours_per_plate.
     */
    private function updateTimeFromHoursPerPlate(): void
    {
        $totalHours = (float) ($this->hours_per_plate ?? 0);
        $this->hours_per_plate_hours = (int) \floor($totalHours);
        $this->hours_per_plate_minutes = (int) \round(($totalHours - $this->hours_per_plate_hours) * 60);

        // Ensure minutes are between 0 and 59
        if ($this->hours_per_plate_minutes >= 60) {
            $this->hours_per_plate_hours += 1;
            $this->hours_per_plate_minutes = 0;
        }
    }

    /**
     * Compute calculation result for display.
     * Called directly in render() to ensure it's always up to date.
     *
     * @return null|array<string, array<string, float>>
     */
    private function computeCalculation(): ?array
    {
        // Need material_id to compute
        if (empty($this->material_id)) {
            return null;
        }

        try {
            $material = PrintMaterial::with('materialType')->find($this->material_id);

            if ($material === null) {
                return null;
            }

            $settings = PrintSetting::current();
            $calculator = new PrintJobCalculator();

            $input = [
                'pieces_per_plate' => $this->pieces_per_plate ?? 1,
                'plates' => $this->plates ?? 1,
                'grams_per_plate' => $this->grams_per_plate ?? 0.0,
                'hours_per_plate' => $this->hours_per_plate ?? 0.0,
                'labor_hours' => $this->labor_hours ?? 0.0,
                'is_first_time_order' => $this->is_first_time_order ?? false,
                'avance_pct_override' => $this->avance_pct_override !== null
                    ? $this->avance_pct_override
                    : null,
                'electricity_rate_dkk_per_kwh' => $settings->electricity_rate_dkk_per_kwh ?? 0,
                'wage_rate_dkk_per_hour' => $settings->wage_rate_dkk_per_hour ?? 0,
                'first_time_fee_dkk' => $settings->first_time_fee_dkk ?? 0,
                'default_avance_pct' => $settings->default_avance_pct ?? 0,
                'price_per_kg_dkk' => $material->price_per_kg_dkk ?? 0,
                'waste_factor_pct' => $material->waste_factor_pct ?? 0,
                'avg_kwh_per_hour' => $material->materialType->avg_kwh_per_hour ?? 0,
            ];

            return $calculator->calculate($input);
        } catch (\Throwable $e) {
            // Log error but don't break the page
            \Log::error('Calculation error: ' . $e->getMessage(), [
                'material_id' => $this->material_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
