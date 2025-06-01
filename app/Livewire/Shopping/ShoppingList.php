<?php

namespace App\Livewire\Shopping;

use App\Models\Category;
use App\Models\Recipe;
use App\Models\RecipeTag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Validate; 

class ShoppingList extends Component
{

    public string $item = '';
    public Collection $sortedItems;

    public Collection $sortedCheckedItems;

    public function mount()
    {
        $this->updateList();
    }

    public function render()
    {
        return view('livewire.shopping.list', ['title' => 'Shopping List']);
    }

    public function updateList()
    {
        $items = \App\Models\ShoppingList::forAuthUser()->where(column: 'status', operator: '=', value: 'active')->orderBy('order', 'ASC')->get();
        $this->sortedItems = $items;

        $checkedItems = \App\Models\ShoppingList::forAuthUser()->where(column: 'status', operator: '=', value: 'checked')->orderBy('order', 'ASC')->get();
        $this->sortedCheckedItems = $checkedItems;
    }

    public function updateOrder($items)
    {
        $order = 1;
        foreach ($items as $item) {
            $item = \App\Models\ShoppingList::find($item);
            $item->order = $order;
            $item->save();
            $order++;
        }
        $this->updateList();
        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function addItem()
    {
        $this->validate([
            'item' => 'required|min:3',
        ]);

        $lastOrder = \App\Models\ShoppingList::forAuthUser()->orderBy('order', 'DESC')->first();
        if (!$lastOrder) {
            $lastOrder = new \App\Models\ShoppingList();
            $lastOrder->order = 0;
        }

        $item = new \App\Models\ShoppingList();
        $item->name = $this->item;
        $item->user_id = auth()->id();
        $item->order = $lastOrder->order + 1;
        $item->save();

        $this->updateList();

        $this->item = '';
        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function check($id)
    {
        $item = \App\Models\ShoppingList::forAuthUser()->find($id);
        if (!$item) {
            return;
        }
        $item->status = 'checked';
        $item->save();

        $this->updateList();

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function uncheck($id)
    {
        $item = \App\Models\ShoppingList::forAuthUser()->find($id);
        $item->status = 'active';
        $item->save();

        $this->updateList();

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function delete($id)
    {
        $item = \App\Models\ShoppingList::forAuthUser()->find($id);
        $item->delete();
        $this->updateList();
        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function clearChecked()
    {
        \App\Models\ShoppingList::forAuthUser()->where(column: 'status', operator: '=', value: 'checked')->delete();
        $this->updateList();
        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }
}
