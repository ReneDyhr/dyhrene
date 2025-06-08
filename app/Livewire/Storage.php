<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Storage extends Component
{
    public string $name;

    /**
     * @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Storage>
     */
    public Collection $storage;

    public int $selectedStorageId;

    /**
     * @var array<int, string>
     */
    public array $itemName = [];

    /**
     * @var array<int, int>
     */
    public array $itemQuantity = [];

    public ?int $confirmingItemId;

    public ?int $editItemId;

    public ?string $editItemName;

    public ?int $editItemQuantity;

    /**
     * @var array<string, string>
     */
    protected array $rules = [
        'name' => 'required|string|max:255',
    ];

    // protected array $listeners = ['updateOrder'];

    public function mount(): void
    {
        $this->loadStorage();
    }

    public function loadStorage(): void
    {
        $this->storage = \App\Models\Storage::orderBy('name')->get();
    }

    public function addStorage(): void
    {
        $this->validate();
        \App\Models\Storage::create(['name' => $this->name]);
        $this->name = '';
        $this->loadStorage();
    }

    public function selectStorage(int $id): void
    {
        $this->selectedStorageId = $id;
        $this->dispatch('storageSelected', id: $id);
    }

    public function render(): View
    {
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();

        return \view('livewire.storage');
    }

    public function addStorageItem(int $storageId): void
    {
        $this->validate([
            'itemName.' . $storageId => 'required|string|max:255',
            'itemQuantity.' . $storageId => 'required|integer|min:1',
        ]);

        \App\Models\StorageItem::create([
            'storage_id' => $storageId,
            'name' => $this->itemName[$storageId],
            'quantity' => $this->itemQuantity[$storageId],
        ]);
        $this->itemName[$storageId] = '';
        $this->itemQuantity[$storageId] = 1;
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
    }

    public function removeItem(int $itemId): void
    {
        \App\Models\StorageItem::where('id', $itemId)->delete();
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
    }

    public function removeItemConfirmed(): void
    {
        if ($this->confirmingItemId !== null) {
            \App\Models\StorageItem::where('id', $this->confirmingItemId)->delete();
            $this->confirmingItemId = null;
            $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
        }
        $this->dispatch('hide-confirm-modal');
    }

    /**
     * Update the order of storage items.
     *
     * @param array<int, int> $orderedIds
     */
    public function updateOrder(int $storageId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            \App\Models\StorageItem::where('id', $id)->update(['sort_order' => $index]);
        }
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
    }

    public function editItem(int $itemId): void
    {
        $item = \App\Models\StorageItem::findOrFail($itemId);
        $this->editItemId = $item->id;
        $this->editItemName = $item->name;
        $this->editItemQuantity = $item->quantity;
        $this->dispatch('show-edit-modal');
    }

    public function updateItem(): void
    {
        $this->validate([
            'editItemName' => 'required|string|max:255',
            'editItemQuantity' => 'required|integer|min:1',
        ]);
        $item = \App\Models\StorageItem::findOrFail($this->editItemId);
        $item->name = (string) $this->editItemName;
        $item->quantity = $this->editItemQuantity ?? 1;
        $item->save();
        $this->dispatch('hide-edit-modal');
        $this->editItemId = null;
        $this->editItemName = null;
        $this->editItemQuantity = null;
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
    }
}
