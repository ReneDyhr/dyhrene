<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Freezer;
use App\Models\FreezerItem;

class FreezerItems extends Component
{
    public $freezerId;
    public $items = [];
    public $name;
    public $quantity;
    public $unit;

    protected $rules = [
        'name' => 'required|string|max:255',
        'quantity' => 'required|integer|min:1',
        'unit' => 'nullable|string|max:50',
    ];

    protected $listeners = ['freezerSelected' => 'setFreezer'];

    public function setFreezer($id)
    {
        $this->freezerId = $id;
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->items = FreezerItem::where('freezer_id', $this->freezerId)->orderBy('name')->get();
    }

    public function addItem()
    {
        $this->validate();
        FreezerItem::create([
            'freezer_id' => $this->freezerId,
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
        return view('livewire.freezer-items');
    }
}
