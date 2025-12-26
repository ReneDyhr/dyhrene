<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs;

use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\PrintOrderSequence;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Create extends Component
{
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

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function save(): \Livewire\Features\SupportRedirects\Redirector
    {
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

        // Generate order number transaction-safely
        $orderNo = $this->generateOrderNumber();

        // Create print job as draft
        $printJob = PrintJob::create([
            'order_no' => $orderNo,
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
            'status' => 'draft',
            'calc_snapshot' => null,
        ]);

        \session()->flash('success', 'Print job created successfully.');

        // @phpstan-ignore return.type
        return $this->redirect(\route('print-jobs.show', $printJob));
    }

    /**
     * Generate order number transaction-safely.
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        return DB::transaction(function () {
            $year = (int) now()->year;

            // SELECT ... FOR UPDATE to lock the row
            $sequence = PrintOrderSequence::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            // If row doesn't exist, create it
            if ($sequence === null) {
                $sequence = PrintOrderSequence::create([
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            // Increment and persist
            $sequence->increment('last_number');
            $sequence->refresh();

            // Format order number
            return \sprintf('%d-%04d', $year, $sequence->last_number);
        });
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

        return \view('livewire.print-jobs.create', \compact('customers', 'materials', 'materialTypes'));
    }
}

