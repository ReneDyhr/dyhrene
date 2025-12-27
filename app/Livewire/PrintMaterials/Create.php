<?php

declare(strict_types=1);

namespace App\Livewire\PrintMaterials;

use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Create extends Component
{
    public int $material_type_id = 0;

    public string $name = '';

    public string $price_per_kg_dkk = '';

    public string $waste_factor_pct = '0';

    public ?string $notes = null;

    public function mount(): void
    {
        $firstType = PrintMaterialType::query()->first();

        if ($firstType !== null) {
            $this->material_type_id = $firstType->id;
        }
    }

    public function save()
    {
        $this->validate([
            'material_type_id' => 'required|exists:print_material_types,id',
            'name' => 'required|string|max:255',
            'price_per_kg_dkk' => 'required|numeric|min:0.01',
            'waste_factor_pct' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ], [], [
            'material_type_id' => 'material type',
        ]);

        // Check unique constraint: (material_type_id, name)
        $exists = PrintMaterial::query()
            ->where('material_type_id', $this->material_type_id)
            ->where('name', $this->name)
            ->exists();

        if ($exists) {
            $this->addError('name', 'A material with this name already exists for the selected material type.');

            return null;
        }

        PrintMaterial::create([
            'material_type_id' => $this->material_type_id,
            'name' => $this->name,
            'price_per_kg_dkk' => $this->price_per_kg_dkk,
            'waste_factor_pct' => $this->waste_factor_pct ?: 0,
            'notes' => $this->notes,
        ]);

        \session()->flash('success', 'Material created successfully.');

        return $this->redirect(\route('print-materials.index'));
    }

    public function render(): View
    {
        $materialTypes = PrintMaterialType::query()->orderBy('name')->get();

        return \view('livewire.print-materials.create', \compact('materialTypes'));
    }
}
