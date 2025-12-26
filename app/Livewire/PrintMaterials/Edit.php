<?php

declare(strict_types=1);

namespace App\Livewire\PrintMaterials;

use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Edit extends Component
{
    public PrintMaterial $material;
    public int $material_type_id = 0;
    public string $name = '';
    public string $price_per_kg_dkk = '';
    public string $waste_factor_pct = '0';
    public ?string $notes = null;

    public function mount(PrintMaterial $material): void
    {
        $this->material = $material;
        $this->material_type_id = $material->material_type_id;
        $this->name = $material->name;
        $this->price_per_kg_dkk = (string) $material->price_per_kg_dkk;
        $this->waste_factor_pct = (string) $material->waste_factor_pct;
        $this->notes = $material->notes;
    }

    public function save(): \Livewire\Features\SupportRedirects\Redirector|null
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

        // Check unique constraint: (material_type_id, name) - excluding current material
        $exists = PrintMaterial::query()
            ->where('material_type_id', $this->material_type_id)
            ->where('name', $this->name)
            ->where('id', '!=', $this->material->id)
            ->exists();

        if ($exists) {
            $this->addError('name', 'A material with this name already exists for the selected material type.');
            return null;
        }

        $this->material->update([
            'material_type_id' => $this->material_type_id,
            'name' => $this->name,
            'price_per_kg_dkk' => $this->price_per_kg_dkk,
            'waste_factor_pct' => $this->waste_factor_pct ?: 0,
            'notes' => $this->notes,
        ]);

        \session()->flash('success', 'Material updated successfully.');

        // @phpstan-ignore return.type
        return $this->redirect(\route('print-materials.index'));
    }

    public function render(): View
    {
        $materialTypes = PrintMaterialType::query()->orderBy('name')->get();

        return \view('livewire.print-materials.edit', \compact('materialTypes'));
    }
}

