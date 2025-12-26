<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs\Components;

use App\Domain\Printing\PrintJobCalculator;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CalculationPanel extends Component
{
    public ?PrintJob $printJob = null;

    /**
     * Job inputs array for live calculation (when no printJob or draft).
     *
     * @var array<string, mixed>
     */
    public array $jobInputs = [];

    /**
     * Calculation result array.
     *
     * @var array<string, array<string, float>>|null
     */
    public ?array $calculationResult = null;

    /**
     * Whether this is a locked job (uses snapshot).
     *
     * @var bool
     */
    public bool $isLocked = false;

    /**
     * Computed property: get calculation result.
     *
     * @return array<string, array<string, float>>|null
     */
    public function getCalculationProperty(): ?array
    {
        if ($this->isLocked && $this->printJob !== null) {
            // For locked jobs, use snapshot
            $snapshot = $this->printJob->calc_snapshot;
            if ($snapshot !== null && is_array($snapshot)) {
                return [
                    'totals' => $snapshot['totals'] ?? [],
                    'costs' => $snapshot['costs'] ?? [],
                    'pricing' => $snapshot['pricing'] ?? [],
                    'profit' => $snapshot['profit'] ?? [],
                ];
            }
            return null;
        }

        // For draft jobs or when using jobInputs, compute live
        return $this->computeCalculation();
    }

    /**
     * Compute calculation using PrintJobCalculator.
     *
     * @return array<string, array<string, float>>|null
     */
    private function computeCalculation(): ?array
    {
        // If we have a printJob, use its data
        if ($this->printJob !== null) {
            $this->printJob->loadMissing(['material', 'material.materialType']);
            $settings = PrintSetting::current();
            $calculator = new PrintJobCalculator();
            $input = $this->buildCalculatorInputFromJob($this->printJob, $settings);
            return $calculator->calculate($input);
        }

        // Otherwise, use jobInputs array
        if (empty($this->jobInputs)) {
            return null;
        }

        // Load material and settings
        $materialId = $this->jobInputs['material_id'] ?? null;
        if ($materialId === null) {
            return null;
        }

        $material = PrintMaterial::with('materialType')->find($materialId);
        if ($material === null) {
            return null;
        }

        $settings = PrintSetting::current();
        $calculator = new PrintJobCalculator();
        $input = $this->buildCalculatorInputFromArray($this->jobInputs, $material, $settings);

        return $calculator->calculate($input);
    }

    /**
     * Build calculator input from PrintJob model.
     *
     * @param PrintJob $job
     * @param PrintSetting $settings
     * @return array<string, mixed>
     */
    private function buildCalculatorInputFromJob(PrintJob $job, PrintSetting $settings): array
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

    /**
     * Build calculator input from jobInputs array.
     *
     * @param array<string, mixed> $inputs
     * @param PrintMaterial $material
     * @param PrintSetting $settings
     * @return array<string, mixed>
     */
    private function buildCalculatorInputFromArray(array $inputs, PrintMaterial $material, PrintSetting $settings): array
    {
        return [
            'pieces_per_plate' => (int) ($inputs['pieces_per_plate'] ?? 1),
            'plates' => (int) ($inputs['plates'] ?? 1),
            'grams_per_plate' => (float) ($inputs['grams_per_plate'] ?? 0),
            'hours_per_plate' => (float) ($inputs['hours_per_plate'] ?? 0),
            'labor_hours' => (float) ($inputs['labor_hours'] ?? 0),
            'is_first_time_order' => (bool) ($inputs['is_first_time_order'] ?? false),
            'avance_pct_override' => isset($inputs['avance_pct_override']) && $inputs['avance_pct_override'] !== '' 
                ? (float) $inputs['avance_pct_override'] 
                : null,
            'electricity_rate_dkk_per_kwh' => $settings->electricity_rate_dkk_per_kwh ?? 0,
            'wage_rate_dkk_per_hour' => $settings->wage_rate_dkk_per_hour ?? 0,
            'first_time_fee_dkk' => $settings->first_time_fee_dkk ?? 0,
            'default_avance_pct' => $settings->default_avance_pct ?? 0,
            'price_per_kg_dkk' => $material->price_per_kg_dkk ?? 0,
            'waste_factor_pct' => $material->waste_factor_pct ?? 0,
            'avg_kwh_per_hour' => $material->materialType->avg_kwh_per_hour ?? 0,
        ];
    }

    public function render(): View
    {
        $calculation = $this->calculation;

        return \view('livewire.print-jobs.components.calculation-panel', [
            'calculation' => $calculation,
        ]);
    }
}

