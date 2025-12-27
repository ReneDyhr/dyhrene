<?php

declare(strict_types=1);

namespace App\Livewire\PrintMaterialTypes;

use App\Models\PrintMaterialType;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Create extends Component
{
    public string $name = '';

    public string $avg_kwh_per_hour = '';

    public function save(): Redirector
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:print_material_types,name',
            'avg_kwh_per_hour' => 'required|numeric|min:0.0001',
        ]);

        PrintMaterialType::create([
            'name' => $this->name,
            'avg_kwh_per_hour' => $this->avg_kwh_per_hour,
        ]);

        \session()->flash('success', 'Material type created successfully.');

        return $this->redirect(\route('print-material-types.index'));
    }

    public function render(): View
    {
        return \view('livewire.print-material-types.create');
    }
}
