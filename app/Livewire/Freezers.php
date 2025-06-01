<?php

namespace App\Livewire;

use Auth;
use Livewire\Component;
use App\Models\Freezer;

class Freezers extends Component
{
    public $name;
    public $freezers;
    public $selectedFreezerId = null;
    public $itemName = [];
    public $itemQuantity = [];
    public $confirmingItemId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    protected $listeners = ['updateOrder'];

    public function mount()
    {
        $this->loadFreezers();
    }

    public function loadFreezers()
    {
        $this->freezers = Freezer::orderBy('name')->get();
    }

    public function addFreezer()
    {
        $this->validate();
        Freezer::create(['name' => $this->name]);
        $this->name = '';
        $this->loadFreezers();
    }

    public function selectFreezer($id)
    {
        $this->selectedFreezerId = $id;
        $this->dispatch('freezerSelected', id: $id);
    }

    public function render()
    {
        $this->freezers = Freezer::with('items')->orderBy('name')->get();
        return view('livewire.freezers');
    }

    public function addFreezerItem($freezerId)
    {
        $this->validate([
            'itemName.' . $freezerId => 'required|string|max:255',
            'itemQuantity.' . $freezerId => 'required|integer|min:1',
        ]);
        
        \App\Models\FreezerItem::create([
            'freezer_id' => $freezerId,
            'name' => $this->itemName[$freezerId],
            'quantity' => $this->itemQuantity[$freezerId],
        ]);
        $this->itemName[$freezerId] = '';
        $this->itemQuantity[$freezerId] = '';
        $this->freezers = Freezer::with('items')->orderBy('name')->get();
    }

    public function removeItem($itemId)
    {
        \App\Models\FreezerItem::where('id', $itemId)->delete();
        $this->freezers = Freezer::with('items')->orderBy('name')->get();

    }

    public function removeItemConfirmed()
    {
        if ($this->confirmingItemId) {
            \App\Models\FreezerItem::where('id', $this->confirmingItemId)->delete();
            $this->confirmingItemId = null;
            $this->freezers = Freezer::with('items')->orderBy('name')->get();
        }
        $this->dispatch('hide-confirm-modal');
    }

    public function updateOrder($freezerId, $orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            \App\Models\FreezerItem::where('id', $id)->update(['sort_order' => $index]);
        }
        $this->freezers = Freezer::with('items')->orderBy('name')->get();

    }
}
