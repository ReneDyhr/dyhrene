<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs;

use App\Domain\Printing\PrintJobCalculator;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\PrintOrderSequence;
use App\Models\PrintSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

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

    public int $hours_per_plate_hours = 0;

    public int $hours_per_plate_minutes = 0;

    public float $labor_hours = 0;

    public bool $is_first_time_order = false;

    public ?float $avance_pct_override = null;

    public function mount(): void
    {
        $this->date = \now()->format('Y-m-d');
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

        // Generate order number transaction-safely
        try {
            $orderNo = $this->generateOrderNumber();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Error generating order number: ' . $e->getMessage(), 0, $e);
        }

        // Ensure order number was generated
        if ($orderNo === '') {
            throw new \RuntimeException('Failed to generate order number. Got: ' . \var_export($orderNo, true));
        }

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

        return $this->redirect(\route('print-jobs.show', $printJob));
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

        return \view('livewire.print-jobs.create', \compact('customers', 'materials', 'materialTypes', 'calculation'));
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
     * Generate order number transaction-safely.
     */
    private function generateOrderNumber(): string
    {
        $result = DB::transaction(function (): string {
            $year = \now()->year;

            // SELECT ... FOR UPDATE to lock the row (SQLite doesn't support lockForUpdate, but transaction still works)
            $query = PrintOrderSequence::query()->where('year', $year);

            // Only use lockForUpdate for databases that support it
            if (DB::getDriverName() !== 'sqlite') {
                $query->lockForUpdate();
            }

            $sequence = $query->first();

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
            $orderNo = \sprintf('%d-%04d', $year, $sequence->last_number);

            // sprintf always returns a string, but validate it's not empty as a safety check
            // @phpstan-ignore-next-line
            if ($orderNo === '') {
                throw new \RuntimeException('Generated order number is empty');
            }

            return $orderNo;
        });

        // Transaction always returns a string from sprintf, but validate as safety check
        // @phpstan-ignore-next-line
        if ($result === '') {
            throw new \RuntimeException('Failed to generate order number. Transaction returned: ' . \var_export($result, true));
        }

        return $result;
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
        if ($this->material_id === null) {
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
                'pieces_per_plate' => $this->pieces_per_plate,
                'plates' => $this->plates,
                'grams_per_plate' => $this->grams_per_plate,
                'hours_per_plate' => $this->hours_per_plate,
                'labor_hours' => $this->labor_hours,
                'is_first_time_order' => $this->is_first_time_order,
                'avance_pct_override' => $this->avance_pct_override,
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
