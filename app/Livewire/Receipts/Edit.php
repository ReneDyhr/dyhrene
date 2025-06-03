<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\UpdateReceiptAction;
use App\Models\Receipt;
use App\Models\ReceiptCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Edit extends Component
{
    public Receipt $receipt;

    public array $data = [];

    public array $items = [];

    public array $itemEdits = [];

    public array $categories = [];

    public function mount(Receipt $receipt): void
    {
        $this->receipt = $receipt;
        $this->data = $receipt->toArray();
        $this->categories = ReceiptCategory::query()
            ->where('user_id', $receipt->user_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
        $this->items = $receipt->items()->with('category')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'amount' => $item->amount,
                'category_id' => $item->category_id,
            ];
        })->toArray();
        $this->itemEdits = \collect($this->items)->keyBy('id')->toArray();
    }

    public function save(UpdateReceiptAction $action): void
    {
        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.vendor' => 'nullable|string|max:255',
            'data.description' => 'nullable|string',
            'data.currency' => 'required|string|max:10',
            'data.total' => 'required|numeric',
            'data.date' => 'required|date',
            'data.file_path' => 'nullable|string',
            'itemEdits.*.name' => 'required|string|max:255',
            'itemEdits.*.quantity' => 'required|integer|min:1',
            'itemEdits.*.amount' => 'required|numeric',
            'itemEdits.*.category_id' => 'required|integer|exists:receipt_categories,id',
        ]);
        $action->handle($this->receipt, $this->data);

        // Save items
        foreach ($this->itemEdits as $id => $item) {
            $receiptItem = $this->receipt->items()->find($id);

            if ($receiptItem) {
                $receiptItem->update([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                    'category_id' => $item['category_id'],
                ]);
            }
        }
        \session()->flash('success', 'Receipt and items updated!');
        $this->redirect(\route('receipts.index'));
    }

    public function addItem(): void
    {
        $defaultCategoryId = $this->categories[0]['id'] ?? null;
        $new = $this->receipt->items()->create([
            'name' => '',
            'quantity' => 1,
            'amount' => 0,
            'category_id' => $defaultCategoryId,
        ]);
        $this->itemEdits[$new->id] = [
            'id' => $new->id,
            'name' => '',
            'quantity' => 1,
            'amount' => 0,
            'category_id' => $defaultCategoryId,
        ];
        $this->mount($this->receipt->refresh());
    }

    public function deleteItem($itemId): void
    {
        $this->receipt->items()->where('id', $itemId)->delete();
        unset($this->itemEdits[$itemId]);
        $this->mount($this->receipt->refresh());
    }

    public function render(): View
    {
        return \view('receipts.edit', [
            'receipt' => $this->receipt,
        ]);
    }
}
