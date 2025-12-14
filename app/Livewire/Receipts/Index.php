<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class Index extends Component
{
    /**
     * @var array<int, string>
     */
    protected $listeners = ['deleteReceipt'];

    /**
     * This method must be public for Livewire event handling.
     */
    public function deleteReceipt(int $id): \Livewire\Features\SupportRedirects\Redirector
    {
        $receipt = Receipt::query()->findOrFail($id);
        \app(\App\Actions\DeleteReceiptAction::class)->handle($receipt);
        \session()->flash('success', 'Receipt deleted!');

        // @phpstan-ignore return.type
        return \redirect()->route('receipts.index');
    }

    public function render(): View
    {
        $receipts = Receipt::query()
            ->with(['user', 'items'])
            ->orderByDesc('date')
            ->get();

        // Group receipts by month
        $receiptsByMonth = $receipts->groupBy(function (Receipt $receipt): string {
            return $receipt->date->format('Y-m');
        })->map(function (Collection $monthReceipts, string $monthKey): array {
            $monthTotal = $monthReceipts->sum(function (Receipt $receipt): float {
                return $receipt->total;
            });

            // Get the first receipt's currency (assuming all receipts in a month have the same currency)
            $firstReceipt = $monthReceipts->first();
            \assert($firstReceipt instanceof Receipt);
            $currency = $firstReceipt->currency ?? 'DKK';

            return [
                'month' => $monthKey,
                'monthName' => $firstReceipt->date->format('F Y'),
                'receipts' => $monthReceipts,
                'total' => $monthTotal,
                'currency' => $currency,
                'count' => $monthReceipts->count(),
            ];
        })->sortKeysDesc();

        return \view('receipts.index', \compact('receiptsByMonth'));
    }
}
