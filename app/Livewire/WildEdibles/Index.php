<?php

declare(strict_types=1);

namespace App\Livewire\WildEdibles;

use App\Enums\WildEdibleTypeEnum;
use App\Models\WildEdible;
use Livewire\Component;

class Index extends Component
{
    /** @var list<string> */
    public array $types = [];

    /** @var list<int> */
    public array $months = [];

    public function updated(): void
    {
        $this->dispatch('wild-edibles-updated', markers: $this->markers());
    }

    public function clearFilters(): void
    {
        $this->types = [];
        $this->months = [];
        $this->dispatch('wild-edibles-updated', markers: $this->markers());
    }

    public function delete(int $id): void
    {
        $edible = WildEdible::query()->forAuthUser()->findOrFail($id);
        $this->authorize('delete', $edible);
        $edible->delete();
        $this->dispatch('wild-edibles-updated', markers: $this->markers());
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return \view('livewire.wild-edibles.index', [
            'edibles' => WildEdible::query()->forAuthUser()->withFilters($this->types, $this->months)->orderBy('name')->get(),
            'typesList' => WildEdibleTypeEnum::cases(),
            'monthsList' => \range(1, 12),
            'markers' => $this->markers(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function markers(): array
    {
        return \array_values(WildEdible::query()->forAuthUser()->withFilters($this->types, $this->months)->orderBy('id')->get()->map(fn(WildEdible $edible): array => [
            'id' => $edible->id, 'name' => $edible->name, 'type' => $edible->type->value,
            'label' => $edible->type->label(), 'color' => $edible->type->color(), 'icon' => $edible->type->markerIcon(),
            'latitude' => (float) $edible->latitude, 'longitude' => (float) $edible->longitude,
            'location_name' => $edible->location_name, 'season' => $edible->seasonLabel(),
            'url' => \route('wild-edibles.show', $edible),
        ])->all());
    }
}
