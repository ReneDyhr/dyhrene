<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Models\InventoryItem;
use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class Show extends Component
{
    public Receipt $receipt;

    /** @var Collection<int, InventoryItem> */
    public Collection $availableItems;

    public function mount(Receipt $receipt): void
    {
        $this->receipt = $receipt->load(['items.category', 'items.inventoryItem']);
        $this->availableItems = InventoryItem::query()->forAuthUser()
            ->orderBy('name')->get();
    }

    public function linkToInventory(int $itemId, int $inventoryItemId): void
    {
        $receiptItem = $this->receipt->items()->findOrFail($itemId);
        $inventoryItem = InventoryItem::query()->forAuthUser()->findOrFail($inventoryItemId);
        $receiptItem->update(['inventory_item_id' => $inventoryItem->id]);
        $this->receipt->load(['items.category', 'items.inventoryItem']);
        \session()->flash('success', 'Linked to ' . $inventoryItem->name);
    }

    public function unlinkFromInventory(int $itemId): void
    {
        $receiptItem = $this->receipt->items()->findOrFail($itemId);
        $receiptItem->update(['inventory_item_id' => null]);
        $this->receipt->load(['items.category', 'items.inventoryItem']);
        \session()->flash('success', 'Unlinked.');
    }

    public function render(): View
    {
        return \view('receipts.show', [
            'receipt' => $this->receipt,
        ]);
    }
}
