<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\InventoryCategory;
use Illuminate\View\View;
use Livewire\Component;

class Categories extends Component
{
    public string $addName = '';

    public string $addColor = '';

    public int $editId = 0;

    public string $editName = '';

    public string $editColor = '';

    public int $deleteId = 0;

    public string $deleteName = '';

    public bool $deleteCheck = false;

    public function mount(): void {}

    public function render(): View
    {
        $categories = InventoryCategory::with([])->orderBy('id', 'DESC')->forAuthUser()->get();

        return \view('livewire.inventory.categories', ['title' => 'Inventory Categories', 'categories' => $categories]);
    }

    public function addCategory(): void
    {
        $this->validate([
            'addName' => 'required|string|max:255',
            'addColor' => 'nullable|string|max:7',
        ]);

        InventoryCategory::query()->create([
            'name' => $this->addName,
            'color' => $this->addColor ?: null,
            'user_id' => \auth()->id(),
        ]);

        $this->addName = '';
        $this->addColor = '';

        $this->redirect(\route('inventory.categories'));
    }

    public function showEditCategory(int $id): void
    {
        $category = InventoryCategory::forAuthUser()->findOrFail($id);
        $this->editId = $category->id;
        $this->editName = $category->name;
        $this->editColor = $category->color ?? '';
        $this->dispatch('showEditInventoryCategoryModal', ['id' => $id, 'name' => $category->name, 'color' => $category->color]);
    }

    public function editCategory(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editColor' => 'nullable|string|max:7',
        ]);

        $category = InventoryCategory::forAuthUser()->findOrFail($this->editId);

        $category->update([
            'name' => $this->editName,
            'color' => $this->editColor ?: null,
        ]);

        $this->editId = 0;
        $this->editName = '';
        $this->editColor = '';

        $this->redirect(\route('inventory.categories'));
    }

    public function showDeleteCategory(int $id): void
    {
        $category = InventoryCategory::forAuthUser()->findOrFail($id);
        $this->deleteId = $category->id;
        $this->deleteName = $category->name;
        $this->dispatch('showDeleteInventoryCategoryModal', ['id' => $id, 'name' => $category->name]);
    }

    public function deleteCategory(): void
    {
        $this->validate([
            'deleteCheck' => 'required|accepted',
        ]);

        $category = InventoryCategory::forAuthUser()->findOrFail($this->deleteId);
        $category->delete();

        $this->deleteId = 0;
        $this->deleteName = '';
        $this->deleteCheck = false;

        $this->redirect(\route('inventory.categories'));
    }
}
