<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\CreateReceiptAction;
use App\Models\ReceiptCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Create extends Component
{
    public array $data = [];

    public array $itemEdits = [];

    public array $categories = [];

    public function mount(): void
    {
        $this->categories = ReceiptCategory::query()
            ->where('user_id', \Auth::id())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
        $this->itemEdits = [];
    }

    public function addItem(): void
    {
        $defaultCategoryId = $this->categories[0]['id'] ?? null;
        $id = \uniqid('new_', true);
        $this->itemEdits[$id] = [
            'name' => '',
            'quantity' => 1,
            'amount' => 0,
            'category_id' => $defaultCategoryId,
        ];
    }

    public function deleteItem($id): void
    {
        unset($this->itemEdits[$id]);
    }

    public function save(CreateReceiptAction $action): void
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
        $receipt = $action->handle(\Auth::user(), $this->data);

        foreach ($this->itemEdits as $item) {
            $receipt->items()->create([
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
                'category_id' => $item['category_id'],
            ]);
        }

        \session()->flash('success', 'Receipt created!');
        $this->redirect(\route('receipts.index'));
    }

    public function render(): View
    {
        return \view('receipts.create', [
            'categories' => $this->categories,
            'itemEdits' => $this->itemEdits,
        ]);
    }
}
