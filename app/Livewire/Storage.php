<?php

namespace App\Livewire;

use Auth;
use Livewire\Component;

class Storage extends Component
{
    public $name;
    public $storage;
    public $selectedStorageId = null;
    public $itemName = [];
    public $itemQuantity = [];
    public $confirmingItemId = null;
    public $editItemId;
    public $editItemName;
    public $editItemQuantity;

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    protected $listeners = ['updateOrder'];

    public function mount()
    {
        $this->loadStorage();
    }

    public function loadStorage()
    {
        $this->storage = \App\Models\Storage::orderBy('name')->get();
    }

    public function addStorage()
    {
        $this->validate();
        \App\Models\Storage::create(['name' => $this->name]);
        $this->name = '';
        $this->loadStorage();
    }

    public function selectStorage($id)
    {
        $this->selectedStorageId = $id;
        $this->dispatch('storageSelected', id: $id);
    }

    public function render()
    {
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
        return view('livewire.storage');
    }

    public function addStorageItem($storageId)
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
        $this->itemQuantity[$storageId] = '';
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
    }

    public function removeItem($itemId)
    {
        \App\Models\StorageItem::where('id', $itemId)->delete();
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();

    }

    public function removeItemConfirmed()
    {
        if ($this->confirmingItemId) {
            \App\Models\StorageItem::where('id', $this->confirmingItemId)->delete();
            $this->confirmingItemId = null;
            $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
        }
        $this->dispatch('hide-confirm-modal');
    }

    public function updateOrder($storageId, $orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            \App\Models\StorageItem::where('id', $id)->update(['sort_order' => $index]);
        }
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();

    }

    public function editItem($itemId)
    {
        $item = \App\Models\StorageItem::findOrFail($itemId);
        $this->editItemId = $item->id;
        $this->editItemName = $item->name;
        $this->editItemQuantity = $item->quantity;
        $this->dispatch('show-edit-modal');
    }

    public function updateItem()
    {
        $this->validate([
            'editItemName' => 'required|string|max:255',
            'editItemQuantity' => 'required|integer|min:1',
        ]);
        $item = \App\Models\StorageItem::findOrFail($this->editItemId);
        $item->name = $this->editItemName;
        $item->quantity = $this->editItemQuantity;
        $item->save();
        $this->dispatch('hide-edit-modal');
        $this->editItemId = null;
        $this->editItemName = null;
        $this->editItemQuantity = null;
        $this->storage = \App\Models\Storage::with('items')->orderBy('name')->get();
    }
}
