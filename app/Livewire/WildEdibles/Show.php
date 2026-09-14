<?php

declare(strict_types=1);

namespace App\Livewire\WildEdibles;

use App\Models\WildEdible;
use Livewire\Component;

class Show extends Component
{
    public WildEdible $wildEdible;

    public function mount(int | WildEdible $wildEdible): void
    {
        $id = $wildEdible instanceof WildEdible ? $wildEdible->id : $wildEdible;
        $this->wildEdible = WildEdible::query()->forAuthUser()->with(['photos', 'pickings'])->findOrFail($id);
        $this->authorize('view', $this->wildEdible);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->wildEdible);
        $this->wildEdible->delete();
        $this->redirectRoute('wild-edibles.index');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return \view('livewire.wild-edibles.show', [
            'markers' => [[
                'id' => $this->wildEdible->id,
                'name' => $this->wildEdible->name,
                'type' => $this->wildEdible->type->value,
                'label' => $this->wildEdible->type->label(),
                'color' => $this->wildEdible->type->color(),
                'icon' => $this->wildEdible->type->markerIcon(),
                'latitude' => (float) $this->wildEdible->latitude,
                'longitude' => (float) $this->wildEdible->longitude,
                'location_name' => $this->wildEdible->location_name,
                'season' => $this->wildEdible->seasonLabel(),
                'url' => \route('wild-edibles.show', $this->wildEdible),
            ]],
        ]);
    }
}
