<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Models\ReceiptCategory;
use App\Models\ReceiptItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MassEditItems extends Component
{
    /**
     * @var array<int, array{id: int, name: string, category_id: int, receipt_id: int, receipt_name: string, receipt_date: string}>
     */
    public array $items = [];

    /**
     * @var array<int, array{id: int, name: string}>
     */
    public array $categories = [];

    public function mount(): void
    {
        // Load categories for the authenticated user
        $this->categories = \array_map(
            // @phpstan-ignore argument.type
            static fn(array $cat): array => [
                'id' => isset($cat['id']) ? (int) $cat['id'] : 0,
                'name' => isset($cat['name']) ? (string) $cat['name'] : '',
            ],
            ReceiptCategory::query()
                ->where('user_id', \Auth::id())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray(),
        );

        // Load latest 1000 receipt items with receipt info (only for authenticated user's receipts)
        // @phpstan-ignore assign.propertyType
        $this->items = ReceiptItem::query()
            ->with(['receipt', 'category'])
            ->whereHas('receipt', function (\Illuminate\Database\Eloquent\Builder $query): void {
                $query->where('user_id', \Auth::id());
            })
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get()
            ->map(function (ReceiptItem $item): array {
                $receipt = $item->receipt;
                \assert($receipt instanceof \App\Models\Receipt);

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category_id' => $item->category_id,
                    'receipt_id' => $item->receipt_id,
                    'receipt_name' => $receipt->name,
                    'receipt_date' => $receipt->date->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    public function save(): void
    {
        $this->validate([
            'items.*.name' => 'required|string|max:255',
            'items.*.category_id' => 'required|integer|exists:receipt_categories,id',
        ]);

        $updated = 0;

        foreach ($this->items as $itemData) {
            $item = ReceiptItem::query()
                ->whereHas('receipt', function (\Illuminate\Database\Eloquent\Builder $query): void {
                    $query->where('user_id', \Auth::id());
                })
                ->find($itemData['id']);

            if ($item === null) {
                continue;
            }

            $item->name = $itemData['name'];
            $item->category_id = $itemData['category_id'];
            $item->save();
            $updated++;
        }

        \session()->flash('success', "Updated {$updated} receipt items!");
        $this->redirect(\route('receipts.mass-edit-items'));
    }

    public function render(): View
    {
        return \view('receipts.mass-edit-items');
    }
}
