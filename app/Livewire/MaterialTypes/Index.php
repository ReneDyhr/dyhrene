<?php

declare(strict_types=1);

namespace App\Livewire\MaterialTypes;

use App\Models\PrintMaterialType;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function delete(int $id): void
    {
        $materialType = PrintMaterialType::query()->findOrFail($id);

        // Check if any materials reference this material type
        if ($materialType->materials()->count() > 0) {
            \session()->flash('error', 'Cannot delete material type because it is referenced by materials.');
            return;
        }

        $materialType->delete();
        \session()->flash('success', 'Material type deleted successfully.');
    }

    public function render(): View
    {
        $materialTypes = PrintMaterialType::query()
            ->orderBy('name')
            ->get();

        return \view('livewire.material-types.index', \compact('materialTypes'));
    }
}

