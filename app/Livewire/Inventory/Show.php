<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\InventoryItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public InventoryItem $item;

    public function mount(InventoryItem $item): void
    {
        \abort_unless($item->user_id === \Auth::id(), 403);

        $this->item = $item->load(['category', 'attachments']);
    }

    public function render(): View
    {
        return \view('livewire.inventory.show', [
            'item' => $this->item,
        ]);
    }
}
