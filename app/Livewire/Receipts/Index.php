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
            ->with(['user', 'items.category'])
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

            // Calculate top 3 categories by expense
            $categoryTotals = [];

            foreach ($monthReceipts as $receipt) {
                foreach ($receipt->items as $item) {
                    $categoryId = $item->category_id;
                    $categoryName = $item->category->name ?? 'Uncategorized';

                    if (!isset($categoryTotals[$categoryId])) {
                        $categoryTotals[$categoryId] = [
                            'name' => $categoryName,
                            'total' => 0.0,
                        ];
                    }
                    $categoryTotals[$categoryId]['total'] += $item->total;
                }
            }

            // Sort by total descending and get top 3
            \usort($categoryTotals, function (array $a, array $b): int {
                return $b['total'] <=> $a['total'];
            });
            $topCategories = \array_slice($categoryTotals, 0, 3);

            // Round each category total to nearest 5
            foreach ($topCategories as &$category) {
                $category['total'] = \round($category['total'] / 5) * 5;
            }
            unset($category);

            return [
                'month' => $monthKey,
                'monthName' => $firstReceipt->date->format('F Y'),
                'receipts' => $monthReceipts,
                'total' => $monthTotal,
                'currency' => $currency,
                'count' => $monthReceipts->count(),
                'topCategories' => $topCategories,
            ];
        })->sortKeysDesc();

        return \view('receipts.index', \compact('receiptsByMonth'));
    }
}
