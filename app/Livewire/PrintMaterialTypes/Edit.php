<?php

declare(strict_types=1);

namespace App\Livewire\PrintMaterialTypes;

use App\Models\PrintMaterialType;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Edit extends Component
{
    public PrintMaterialType $materialType;

    public string $name = '';

    public string $avg_kwh_per_hour = '';

    public function mount(PrintMaterialType $materialType): void
    {
        $this->materialType = $materialType;
        $this->name = $materialType->name;
        $this->avg_kwh_per_hour = (string) $materialType->avg_kwh_per_hour;
    }

    public function save(): ?Redirector
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:print_material_types,name,' . $this->materialType->id,
            'avg_kwh_per_hour' => 'required|numeric|min:0.0001',
        ]);

        $this->materialType->update([
            'name' => $this->name,
            'avg_kwh_per_hour' => $this->avg_kwh_per_hour,
        ]);

        \session()->flash('success', 'Material type updated successfully.');

        return $this->redirect(\route('print-material-types.index'));
    }

    public function render(): View
    {
        return \view('livewire.print-material-types.edit');
    }
}
