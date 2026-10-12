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

    /**
     * Update the order of storage items.
     *
     * @param array<int, int|string> $orderedIds
     */
    public function updateOrder(int $storageId, array $orderedIds): void
    {
        $storage = \App\Models\Storage::with('items')->find($storageId);

        if ($storage === null) {
            return;
        }

        /** @var list<int> $currentIds */
        $currentIds = $storage->items->pluck('id')->all();
        $currentSet = \array_fill_keys($currentIds, true);
        $validIds = [];

        foreach ($orderedIds as $id) {
            if (\is_int($id)) {
                $validIds[] = $id;
            } elseif (\ctype_digit($id)) {
                $validIds[] = (int) $id;
            }
        }

        $orderedIds = \array_values(\array_filter($validIds, static fn(int $id): bool => isset($currentSet[$id])));
        $orderedIds = \array_values(\array_unique([...$orderedIds, ...\array_diff($currentIds, $orderedIds)]));

        foreach ($orderedIds as $index => $id) {
            \App\Models\StorageItem::query()->where('storage_id', $storageId)->where('id', $id)->update(['sort_order' => $index]);
        }
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
    }

    public function moveItemUp(int $storageId, int $itemId): void
    {
        $this->moveItem($storageId, $itemId, -1);
    }

    public function moveItemDown(int $storageId, int $itemId): void
    {
        $this->moveItem($storageId, $itemId, 1);
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

    private function moveItem(int $storageId, int $itemId, int $direction): void
    {
        $storage = \App\Models\Storage::with('items')->find($storageId);
        $items = $storage?->items->values();

        if ($items === null) {
            return;
        }

        $index = $items->search(static fn(\App\Models\StorageItem $item): bool => $item->id === $itemId);

        if (!\is_int($index) || $index + $direction < 0 || $index + $direction >= $items->count()) {
            return;
        }

        /** @var list<int> $ids */
        $ids = $items->pluck('id')->all();
        $target = $index + $direction;
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        $this->updateOrder($storageId, $ids);
    }
}
