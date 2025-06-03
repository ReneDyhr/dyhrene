<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\StorageItem;
use Illuminate\View\View;
use Livewire\Component;

class StorageItems extends Component
{
    public int $storageId;

    /** @var array<int, \App\Models\StorageItem> */
    public array $items = [];

    public string $name;

    public int $quantity;

    /** @var array<string, string> */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'quantity' => 'required|integer|min:1',
    ];

    /**
     * @var array<string, string>
     */
    protected $listeners = ['storageSelected' => 'setStorage'];

    public function setStorage(int $id): void
    {
        $this->storageId = $id;
        $this->loadItems();
    }

    public function loadItems(): void
    {
        $this->items = StorageItem::where('storage_id', $this->storageId)->orderBy('name')->get()->all();
    }

    public function addItem(): void
    {
        $this->validate();
        StorageItem::create([
            'storage_id' => $this->storageId,
            'name' => $this->name,
            'quantity' => $this->quantity,
        ]);
        $this->name = '';
        $this->quantity = 1;
        $this->loadItems();
    }

    public function render(): View
    {
        return \view('livewire.storage-items');
    }
}
