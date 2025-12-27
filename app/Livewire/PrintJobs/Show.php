<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs;

use App\Domain\Printing\PrintJobCalculator;
use App\Models\PrintActivityLog;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Show extends Component
{
    public PrintJob $printJob;

    public string $date = '';

    public ?int $customer_id = null;

    public string $description = '';

    public ?string $internal_notes = null;

    /**
     * @var null|array<string, mixed>
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
            $snapshot = $printJob->calc_snapshot;

            if ($snapshot !== null) {
                // Extract calculation data from snapshot (snapshot has totals, costs, pricing, profit at root)
                $this->calculation = [
                    'totals' => $snapshot['totals'] ?? [],
                    'costs' => $snapshot['costs'] ?? [],
                    'pricing' => $snapshot['pricing'] ?? [],
                    'profit' => $snapshot['profit'] ?? [],
                ];
            } else {
                // Handle gracefully if snapshot is missing or invalid
                $this->calculation = null;
            }
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
     * Unlock the print job by clearing snapshot and updating status.
     */
    public function unlock(): Redirector
    {
        // Guard: if not locked, redirect to edit
        $this->printJob->refresh();

        if (!$this->printJob->isLocked()) {
            \session()->flash('error', 'This job is not locked.');

            return $this->redirect(\route('print-jobs.edit', $this->printJob));
        }

        // Wrap in database transaction
        DB::transaction(function (): void {
            // Update job: status='draft', locked_at=null, calc_snapshot=null
            // Preserve current field values (do not restore from snapshot)
            $this->printJob->update([
                'status' => 'draft',
                'locked_at' => null,
                'calc_snapshot' => null,
            ]);

            // Log activity: create ActivityLog entry with action='unlocked'
            PrintActivityLog::create([
                'print_job_id' => $this->printJob->id,
                'action' => 'unlocked',
                'user_id' => \auth()->id(),
                'metadata' => null,
            ]);
        });

        \session()->flash('success', 'Print job unlocked successfully. You can now edit calculation inputs.');

        return $this->redirect(\route('print-jobs.edit', $this->printJob));
    }

    public function render(): View
    {
        $customers = PrintCustomer::query()->active()->orderBy('name')->get();

        // Load recent activity logs for this job
        $activityLogs = $this->printJob->activityLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return \view('livewire.print-jobs.show', \compact('customers', 'activityLogs'));
    }

    /**
     * Build calculator input array from print job and settings.
     *
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
}
