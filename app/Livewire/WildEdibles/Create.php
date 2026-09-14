<?php

declare(strict_types=1);

namespace App\Livewire\WildEdibles;

use App\Actions\WildEdibles\CreateWildEdibleAction;
use App\Actions\WildEdibles\StoreWildEdiblePhotoAction;
use App\Enums\WildEdibleTypeEnum;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $type = 'other';

    public string $name = '';

    public string $description = '';

    public string $location_name = '';

    public string $latitude = '';

    public string $longitude = '';

    public ?int $season_start_month = null;

    public ?int $season_end_month = null;

    public bool $season_all_year = false;

    public ?UploadedFile $photo = null;

    public function mount(): void
    {
        /** @var array{latitude: float|int|string, longitude: float|int|string} $center */
        $center = \config('wild-edibles.default_center');
        $this->latitude = (string) $center['latitude'];
        $this->longitude = (string) $center['longitude'];
    }

    public function save(CreateWildEdibleAction $create, StoreWildEdiblePhotoAction $storePhoto): void
    {
        $this->validate();

        if (!$this->validateSeason()) {
            return;
        }

        try {
            $edible = DB::transaction(function () use ($create, $storePhoto): \App\Models\WildEdible {
                $user = User::query()->findOrFail((int) \auth()->id());
                $edible = $create->handle($user, $this->attributes());

                if ($this->photo !== null) {
                    $storePhoto->handle($user, $edible, $this->photo);
                }

                return $edible;
            });
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->redirectRoute('wild-edibles.show', ['wildEdible' => $edible]);
    }

    public function updatedSeasonAllYear(bool $value): void
    {
        if ($value) {
            $this->season_start_month = null;
            $this->season_end_month = null;
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return \view('livewire.wild-edibles.form', ['editing' => false, 'types' => WildEdibleTypeEnum::cases()]);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'type' => ['required', 'in:' . \implode(',', \array_column(WildEdibleTypeEnum::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'season_start_month' => ['nullable', 'integer', 'between:1,12'],
            'season_end_month' => ['nullable', 'integer', 'between:1,12'],
            'season_all_year' => ['boolean'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
        ];
    }

    /** @return array<string, mixed> */
    private function attributes(): array
    {
        return [
            'type' => $this->type,
            'name' => \trim($this->name),
            'description' => $this->nullable($this->description),
            'location_name' => $this->nullable($this->location_name),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'season_start_month' => $this->season_start_month,
            'season_end_month' => $this->season_end_month,
            'season_all_year' => $this->season_all_year,
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

    private function nullable(string $value): ?string
    {
        return \trim($value) === '' ? null : \trim($value);
    }
}
