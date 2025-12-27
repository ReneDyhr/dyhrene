<?php

declare(strict_types=1);

namespace App\Livewire\PrintJobs;

use App\Domain\Printing\PrintJobCalculator;
use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterialType;
use App\Models\PrintSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public ?string $statusFilter = null;

    #[Url(as: 'customer')]
    public ?int $customerFilter = null;

    #[Url(as: 'material_type')]
    public ?int $materialTypeFilter = null;

    /**
     * Delete a print job (only if draft).
     */
    public function delete(int $id): void
    {
        $printJob = PrintJob::query()->findOrFail($id);

        if (!$printJob->isDraft()) {
            \session()->flash('error', 'Only draft jobs can be deleted.');

            return;
        }

        $printJob->delete();
        \session()->flash('success', 'Print job deleted successfully.');
    }

    public function render(): View
    {
        $query = PrintJob::query()
            ->with(['customer', 'material', 'material.materialType'])
            ->orderBy('date', 'desc')
            ->orderBy('order_no', 'desc');

        // Search: order_no, description, customer name
        if ($this->search !== '') {
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) {
                $q->where('order_no', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhereHas('customer', function (\Illuminate\Database\Eloquent\Builder $customerQuery) {
                        $customerQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Status filter
        if ($this->statusFilter !== null && $this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        // Customer filter
        if ($this->customerFilter !== null) {
            $query->where('customer_id', $this->customerFilter);
        }

        // Material type filter
        if ($this->materialTypeFilter !== null) {
            $query->whereHas('material', function (\Illuminate\Database\Eloquent\Builder $materialQuery) {
                $materialQuery->where('material_type_id', $this->materialTypeFilter);
            });
        }

        $printJobs = $query->paginate(25);

        // Load settings once for calculations
        $settings = PrintSetting::current();
        $calculator = new PrintJobCalculator();

        // Compute calculations for each job
        foreach ($printJobs as $job) {
            if ($job->isDraft()) {
                // Compute live for draft jobs
                $input = $this->buildCalculatorInput($job, $settings);
                $calculation = $calculator->calculate($input);
                $job->calculation = $calculation;
            } else {
                // Use snapshot for locked jobs
                $snapshot = $job->calc_snapshot;

                if ($snapshot !== null && \is_array($snapshot)) {
                    // Extract calculation data from snapshot (snapshot has totals, costs, pricing, profit at root)
                    $job->calculation = [
                        'totals' => $snapshot['totals'] ?? [],
                        'costs' => $snapshot['costs'] ?? [],
                        'pricing' => $snapshot['pricing'] ?? [],
                        'profit' => $snapshot['profit'] ?? [],
                    ];
                } else {
                    // Handle gracefully if snapshot is missing or invalid
                    $job->calculation = [
                        'totals' => ['total_pieces' => 0],
                        'costs' => ['total_cost' => 0],
                        'pricing' => ['sales_price' => 0],
                        'profit' => ['profit' => 0, 'profit_per_piece' => 0],
                    ];
                }
            }
        }

        // Load filter options
        $customers = PrintCustomer::query()->active()->orderBy('name')->get();
        $materialTypes = PrintMaterialType::query()->orderBy('name')->get();

        return \view('livewire.print-jobs.index', \compact('printJobs', 'customers', 'materialTypes'));
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
