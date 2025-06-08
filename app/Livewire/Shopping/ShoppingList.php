<?php

declare(strict_types=1);

namespace App\Livewire\Shopping;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class ShoppingList extends Component
{
    public string $item = '';

    /**
     * @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShoppingList>
     */
    public Collection $sortedItems;

    /**
     * @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShoppingList>
     */
    public Collection $sortedCheckedItems;

    public function mount(): void
    {
        $this->updateList();
    }

    public function render(): View
    {
        return \view('livewire.shopping.list', ['title' => 'Shopping List']);
    }

    public function updateList(): void
    {
        $items = \App\Models\ShoppingList::forAuthUser()->where(column: 'status', operator: '=', value: 'active')->orderBy('order', 'ASC')->get();
        $this->sortedItems = $items;

        $checkedItems = \App\Models\ShoppingList::forAuthUser()->where(column: 'status', operator: '=', value: 'checked')->orderBy('order', 'ASC')->get();
        $this->sortedCheckedItems = $checkedItems;
    }

    /**
     * Update the order of the items in the shopping list.
     *
     * @param \App\Models\ShoppingList[] $items
     */
    public function updateOrder(array $items): void
    {
        $order = 1;

        foreach ($items as $item) {
            $item = \App\Models\ShoppingList::find($item);

            if ($item === null) {
                continue;
            }

            $item->order = $order;
            $item->save();
            $order++;
        }
        $this->updateList();

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function addItem(): void
    {
        $this->validate([
            'item' => 'required|min:3',
        ]);

        $lastOrder = \App\Models\ShoppingList::forAuthUser()->orderBy('order', 'DESC')->first();

        if ($lastOrder === null) {
            $lastOrder = new \App\Models\ShoppingList();
            $lastOrder->order = 0;
        }

        $item = new \App\Models\ShoppingList();
        $item->name = $this->item;
        $item->user_id = (int) \auth()->id();
        $item->order = $lastOrder->order + 1;
        $item->save();

        $this->updateList();

        $this->item = '';

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function check(int $id): void
    {
        $item = \App\Models\ShoppingList::forAuthUser()->find($id);

        if ($item === null) {
            return;
        }
        $item->status = 'checked';
        $item->save();

        $this->updateList();

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function uncheck(int $id): void
    {
        $item = \App\Models\ShoppingList::forAuthUser()->find($id);

        if ($item === null) {
            return;
        }

        $item->status = 'active';
        $item->save();

        $this->updateList();

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function delete(int $id): void
    {
        $item = \App\Models\ShoppingList::forAuthUser()->find($id);

        if ($item === null) {
            return;
        }

        $item->delete();
        $this->updateList();

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }

    public function clearChecked(): void
    {
        \App\Models\ShoppingList::forAuthUser()->where(column: 'status', operator: '=', value: 'checked')->delete();
        $this->updateList();

        /** @var \App\Models\User */
        $user = Auth::user();
        \broadcast(new \App\Events\ShoppingList($user, 'update', []));
    }
}
