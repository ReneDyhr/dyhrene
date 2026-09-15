@section('title', $editing ? 'Redigér vild plante' : 'Tilføj vild plante')
<x-layouts.app-shell :area="\App\Enums\AppArea::Nature">
    <div class="view-head">
        <h1>{{ $editing ? 'Redigér vild plante' : 'Tilføj vild plante' }}</h1>
        <p>Udfyld oplysninger og placér planten på kortet.</p>
    </div>

    <form wire:submit="save" class="card">
        <div class="form-group">
            <label>Type</label>
            <select wire:model="type" class="form-control">
                @foreach ($types as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
            @error('type')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Navn</label>
            <input wire:model="name" class="form-control">
            @error('name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Beskrivelse</label>
            <textarea wire:model="description" class="form-control"></textarea>
            @error('description')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Stednavn</label>
            <input wire:model="location_name" class="form-control">
        </div>

        <div class="form-group">
            <label>Sted</label>
            <div id="wild-edible-picker" wire:ignore data-latitude="{{ $latitude }}" data-longitude="{{ $longitude }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map wild-edible-map-picker"></div>
            <input id="wild-edible-latitude" type="hidden" wire:model="latitude">
            <input id="wild-edible-longitude" type="hidden" wire:model="longitude">
            @error('latitude')<span class="text-danger">{{ $message }}</span>@enderror
            @error('longitude')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Sæson</label>
            <label style="font-weight: normal; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" wire:model="season_all_year"> Tilgængelig hele året
            </label>
            <div class="wild-edible-season-fields" style="margin-top: 10px;">
                <div>
                    <label>Startmåned</label>
                    <select wire:model="season_start_month" class="form-control" @disabled($season_all_year)>
                        <option value="">Ukendt</option>
                        @foreach (\range(1, 12) as $month)
                            <option value="{{ $month }}">{{ \date('F', \mktime(0, 0, 0, $month, 1)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Slutmåned</label>
                    <select wire:model="season_end_month" class="form-control" @disabled($season_all_year)>
                        <option value="">Ukendt</option>
                        @foreach (\range(1, 12) as $month)
                            <option value="{{ $month }}">{{ \date('F', \mktime(0, 0, 0, $month, 1)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @error('season_start_month')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label>Foto (ét billede pr. upload)</label>
            <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
            @error('photo')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        @if ($editing && $wildEdible->photos->isNotEmpty())
            <div class="section-title">Eksisterende billeder</div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                @foreach ($wildEdible->photos as $photo)
                    <div>
                        <img src="{{ route('wild-edibles.photo', $photo) }}" alt="{{ $photo->original_file_name }}" style="max-width: 180px; max-height: 130px;">
                        <br>
                        <button type="button" wire:click="deletePhoto({{ $photo->id }})" class="btn btn-danger btn-xs" wire:confirm="Slet dette billede?">Slet</button>
                    </div>
                @endforeach
            </div>
        @endif

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn btn-primary" type="submit">Gem</button>
            <a class="btn btn-default" href="{{ $editing ? route('wild-edibles.show', $wildEdible) : route('wild-edibles.index') }}" wire:navigate>Annuller</a>
        </div>
    </form>
</x-layouts.app-shell>
@push('scripts')
@if (config('wild-edibles.google_maps_api_key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ \urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>
@endif
@endpush
