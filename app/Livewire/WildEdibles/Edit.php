<?php

declare(strict_types=1);

namespace App\Livewire\WildEdibles;

use App\Actions\WildEdibles\StoreWildEdiblePhotoAction;
use App\Enums\WildEdibleTypeEnum;
use App\Models\User;
use App\Models\WildEdible;
use App\Support\WildEdibles\ImageUploadGuard;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public WildEdible $wildEdible;

    public string $type = '';

    public string $name = '';

    public ?string $description = '';

    public ?string $location_name = '';

    public string $latitude = '';

    public string $longitude = '';

    public ?int $season_start_month = null;

    public ?int $season_end_month = null;

    public bool $season_all_year = false;

    public ?UploadedFile $photo = null;

    public function mount(int | WildEdible $wildEdible): void
    {
        $id = $wildEdible instanceof WildEdible ? $wildEdible->id : $wildEdible;
        $this->wildEdible = WildEdible::query()->forAuthUser()->findOrFail($id);
        $this->authorize('view', $this->wildEdible);
        $this->fill($this->wildEdible->only(['name', 'description', 'location_name', 'latitude', 'longitude', 'season_start_month', 'season_end_month', 'season_all_year']));
        $this->type = $this->wildEdible->type->value;
        $this->description ??= '';
        $this->location_name ??= '';
    }

    public function save(StoreWildEdiblePhotoAction $storePhoto): void
    {
        $this->authorize('update', $this->wildEdible);
        $this->validate();

        if ($this->photo !== null && ImageUploadGuard::isAnimated($this->photo)) {
            $this->addError('photo', 'Animated images are not supported.');

            return;
        }

        if (!$this->validateSeason()) {
            return;
        }
        $this->wildEdible->update([
            'type' => $this->type, 'name' => \trim($this->name), 'description' => $this->nullable($this->description),
            'location_name' => $this->nullable($this->location_name), 'latitude' => $this->latitude, 'longitude' => $this->longitude,
            'season_start_month' => $this->season_start_month, 'season_end_month' => $this->season_end_month,
            'season_all_year' => $this->season_all_year,
        ]);

        if ($this->photo !== null) {
            $user = User::query()->findOrFail((int) \auth()->id());
            $storePhoto->handle($user, $this->wildEdible, $this->photo);
        }
        $this->redirectRoute('wild-edibles.show', ['wildEdible' => $this->wildEdible]);
    }

    public function updatedSeasonAllYear(bool $value): void
    {
        if ($value) {
            $this->season_start_month = null;
            $this->season_end_month = null;
        }
    }

    public function deletePhoto(int $photo): void
    {
        $model = $this->wildEdible->photos()->findOrFail($photo);
        $this->authorize('delete', $model);
        $model->delete();
        $this->wildEdible->unsetRelation('photos');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return \view('livewire.wild-edibles.form', ['editing' => true, 'types' => WildEdibleTypeEnum::cases()]);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'type' => ['required', 'in:' . \implode(',', \array_column(WildEdibleTypeEnum::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
            'location_name' => ['nullable', 'string', 'max:255'], 'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'], 'season_start_month' => ['nullable', 'integer', 'between:1,12'],
            'season_end_month' => ['nullable', 'integer', 'between:1,12'], 'season_all_year' => ['boolean'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
        ];
    }

    private function validateSeason(): bool
    {
        $valid = true;

        if ($this->season_all_year && ($this->season_start_month !== null || $this->season_end_month !== null)) {
            $this->addError('season_start_month', 'All-year availability cannot have month endpoints.');
            $valid = false;
        }

        if (!$this->season_all_year && (($this->season_start_month === null) !== ($this->season_end_month === null))) {
            $this->addError('season_start_month', 'Both season months are required for a partial season.');
            $valid = false;
        }

        return $valid;
    }

    private function nullable(?string $value): ?string
    {
        if ($value === null || \trim($value) === '') {
            return null;
        }

        return \trim($value);
    }
}
