<?php

declare(strict_types=1);

namespace App\Livewire\WildEdibles;

use App\Actions\WildEdibles\CreatePickingAction;
use App\Models\User;
use App\Models\WildEdible;
use Livewire\Component;

class CreatePicking extends Component
{
    public WildEdible $wildEdible;

    public string $picked_at = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $comment = '';

    public function mount(int | WildEdible $wildEdible): void
    {
        $id = $wildEdible instanceof WildEdible ? $wildEdible->id : $wildEdible;
        $this->wildEdible = WildEdible::query()->forAuthUser()->findOrFail($id);
        $this->authorize('view', $this->wildEdible);
        $this->picked_at = \now()->toDateString();
        $this->latitude = $this->wildEdible->latitude;
        $this->longitude = $this->wildEdible->longitude;
    }

    public function save(CreatePickingAction $create): void
    {
        $this->validate();
        $user = User::query()->findOrFail((int) \auth()->id());
        $create->handle($user, $this->wildEdible, [
            'picked_at' => $this->picked_at, 'latitude' => $this->latitude, 'longitude' => $this->longitude,
            'comment' => \trim($this->comment) === '' ? null : \trim($this->comment),
        ]);
        $this->redirectRoute('wild-edibles.show', ['wildEdible' => $this->wildEdible]);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return \view('livewire.wild-edibles.picking');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return ['picked_at' => ['required', 'date'], 'latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180'], 'comment' => ['nullable', 'string']];
    }
}
