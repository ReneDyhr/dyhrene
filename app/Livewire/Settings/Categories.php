<?php

namespace App\Livewire\Settings;

use App\Models\Category;
use App\Models\Icon;
use App\Models\Recipe;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Validate; 

class Categories extends Component
{
    public string $addName = '';
    public int $addIcon = 0;

    public int $editId = 0;
    public string $editName = '';
    public int $editIcon = 0;

    public int $deleteId = 0;
    public string $deleteName = '';
    public bool $deleteCheck = false;

    public function mount()
    {
    }
    public function render()
    {
        $categories = Category::with(['icon'])->orderBy('id', 'DESC')->forAuthUser()->get();
        $icons = Icon::orderBy('id', 'DESC')->get();
        return view('livewire.settings.categories', ['title' => 'Categories', 'categories' => $categories, 'icons' => $icons]);
    }

    public function addCategory()
    {
        $this->validate([
            'addName' => 'required|string|max:255',
            'addIcon' => 'required|int|exists:icons,id',
        ]);


        $icon = Icon::where('id', $this->addIcon)->firstOrFail();

        $id = Category::create([
            'name' => $this->addName,
            'slug' => Str::slug($this->addName),
            'icon_id' => $icon->id,
            'user_id' => auth()->id(),
        ]);

        Category::where('id', '=', $id)->update([
            'slug' => Str::slug($id . '-' . $this->addName),
        ]);

        $this->addName = '';
        $this->addIcon = 0;

        return redirect()->route('settings.categories');
    }

    public function showEditCategory(int $id)
    {
        $category = Category::where('id', $id)->firstOrFail();
        $this->editId = $category->id;
        $this->editName = $category->name;
        $this->editIcon = $category->icon_id;
        $this->dispatch('showEditCategoryModal', ['id' => $id, 'name' => $category->name, 'icon' => $category->icon_id]);
    }

    public function editCategory()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editIcon' => 'required|int|exists:icons,id',
        ]);

        $category = Category::where('id', $this->editId)->firstOrFail();
        $icon = Icon::where('id', $this->editIcon)->firstOrFail();

        $category->update([
            'name' => $this->editName,
            'slug' => Str::slug($this->editId . '-' . $this->editName),
            'icon_id' => $icon->id,
        ]);

        $this->editId = 0;
        $this->editName = '';
        $this->editIcon = 0;

        return redirect()->route('settings.categories');
    }

    public function showDeleteCategory(int $id)
    {
        $category = Category::where('id', $id)->firstOrFail();
        $this->deleteId = $category->id;
        $this->deleteName = $category->name;
        $this->dispatch('showDeleteCategoryModal', ['id' => $id, 'name' => $category->name]);
    }

    public function deleteCategory()
    {
        $this->validate([
            'deleteCheck' => 'required|accepted',
        ]);

        $category = Category::where('id', $this->deleteId)->firstOrFail();
        $category->delete();

        $this->deleteId = 0;
        $this->deleteName = '';
        $this->deleteCheck = false;

        return redirect()->route('settings.categories');
    }
}
