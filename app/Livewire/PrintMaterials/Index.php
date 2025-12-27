<?php

declare(strict_types=1);

namespace App\Livewire\PrintMaterials;

use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $materialTypeFilter = null;

    public function delete(int $id): void
    {
        $material = PrintMaterial::query()->findOrFail($id);
        $material->delete();
        \session()->flash('success', 'Material deleted successfully.');
    }

    public function render(): View
    {
        $query = PrintMaterial::query()
            ->with('materialType')
            ->orderBy('name');

        if ($this->search !== '') {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->materialTypeFilter !== null) {
            $query->where('material_type_id', $this->materialTypeFilter);
        }

        $materials = $query->paginate(25);
        $materialTypes = PrintMaterialType::query()->orderBy('name')->get();

        return \view('livewire.print-materials.index', \compact('materials', 'materialTypes'));
    }
}
