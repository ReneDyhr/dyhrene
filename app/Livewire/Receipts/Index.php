<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;
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
        $receiptsByMonth = $receipts->groupBy(function ($receipt) {
            return $receipt->date->format('Y-m');
        })->map(function ($monthReceipts, $monthKey) {
            $monthTotal = $monthReceipts->sum(function ($receipt) {
                return $receipt->total;
            });

            // Get the first receipt's currency (assuming all receipts in a month have the same currency)
            $currency = $monthReceipts->first()->currency ?? 'DKK';

            return [
                'month' => $monthKey,
                'monthName' => $monthReceipts->first()->date->format('F Y'),
                'receipts' => $monthReceipts,
                'total' => $monthTotal,
                'currency' => $currency,
                'count' => $monthReceipts->count(),
            ];
        })->sortKeysDesc();

        return \view('receipts.index', \compact('receiptsByMonth'));
    }
}
