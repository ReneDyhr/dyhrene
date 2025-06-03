<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Storage;
use App\Models\StorageItem;

class StorageItems extends Component
{
    public $storageId;
    public $items = [];
    public $name;
    public $quantity;
    public $unit;

    protected $rules = [
        'name' => 'required|string|max:255',
        'quantity' => 'required|integer|min:1',
        'unit' => 'nullable|string|max:50',
    ];

    protected $listeners = ['storageSelected' => 'setStorage'];

    public function setStorage($id)
    {
        $this->storageId = $id;
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->items = StorageItem::where('storage_id', $this->storageId)->orderBy('name')->get();
    }

    public function addItem()
    {
        $this->validate();
        StorageItem::create([
            'storage_id' => $this->storageId,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
        ]);
        $this->name = '';
        $this->quantity = '';
        $this->unit = '';
        $this->loadItems();
    }

    public function render()
    {
        return view('livewire.storage-items');
    }
}
