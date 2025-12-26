<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs;

use App\Models\PrintActivityLog;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

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
    public float $labor_hours = 0;
    public bool $is_first_time_order = false;
    public ?float $avance_pct_override = null;

    public function mount(PrintJob $printJob): \Livewire\Features\SupportRedirects\Redirector|null
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

        return null;
    }

    public function save(): \Livewire\Features\SupportRedirects\Redirector
    {
        // Guard: if locked, redirect to show
        $this->printJob->refresh();
        if ($this->printJob->isLocked()) {
            \session()->flash('error', 'Locked jobs cannot be edited. Please unlock first.');
            return $this->redirect(\route('print-jobs.show', $this->printJob));
        }

        $this->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'internal_notes' => 'nullable|string',
            'customer_id' => 'required|exists:print_customers,id',
            'material_id' => 'required|exists:print_materials,id',
            'pieces_per_plate' => 'required|integer|min:1|max:100',
            'plates' => 'required|integer|min:1|max:10',
            'grams_per_plate' => 'required|numeric|min:0|max:999',
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
    public function lock(): \Livewire\Features\SupportRedirects\Redirector
    {
        // Guard: if already locked, redirect to show
        $this->printJob->refresh();
        if ($this->printJob->isLocked()) {
            \session()->flash('error', 'This job is already locked.');
            return $this->redirect(\route('print-jobs.show', $this->printJob));
        }

        // Validate all required fields
        $this->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'customer_id' => 'required|exists:print_customers,id',
            'material_id' => 'required|exists:print_materials,id',
            'pieces_per_plate' => 'required|integer|min:1|max:100',
            'plates' => 'required|integer|min:1|max:10',
            'grams_per_plate' => 'required|numeric|min:0|max:999',
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
                'locked_at' => now(),
                'calc_snapshot' => $snapshot,
            ]);

            // Log activity: create ActivityLog entry with action='locked'
            PrintActivityLog::create([
                'print_job_id' => $this->printJob->id,
                'action' => 'locked',
                'user_id' => auth()->id(),
                'metadata' => null,
            ]);
        });

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

        return \view('livewire.print-jobs.edit', \compact('customers', 'materials', 'materialTypes'));
    }
}

