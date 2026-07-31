<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Actions\DeleteInventoryItemAction;
use App\Enums\InventoryStatusEnum;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'category')]
    public ?int $categoryFilter = null;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function delete(int $id): void
    {
        $item = InventoryItem::query()->forAuthUser()->findOrFail($id);

        (new DeleteInventoryItemAction())->handle($item);
        \session()->flash('success', 'Item deleted.');
    }

    public function render(): View
    {
        $query = InventoryItem::query()
            ->with(['category', 'attachments'])
            ->forAuthUser();

        if ($this->search !== '') {
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q): void {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('brand', 'like', "%{$this->search}%")
                    ->orWhere('model', 'like', "%{$this->search}%")
                    ->orWhere('serial_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->categoryFilter !== null) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $items = $query->orderByDesc('acquisition_date')->paginate(25);
        $categories = InventoryCategory::query()->forAuthUser()->orderBy('name')->get();
        $statuses = InventoryStatusEnum::cases();

        return \view('livewire.inventory.index', [
            'title' => 'Inventory',
            'items' => $items,
            'categories' => $categories,
            'statuses' => $statuses,
        ]);
    }
}
