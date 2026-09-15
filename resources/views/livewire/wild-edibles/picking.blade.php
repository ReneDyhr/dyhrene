@section('title', 'Tilføj plukning')
<x-layouts.app-shell :area="\App\Enums\AppArea::Nature">
    <div class="view-head">
        <h1>Tilføj plukning: {{ $wildEdible->name }}</h1>
        <p>Registrér dato og sted for plukningen.</p>
    </div>

    <form wire:submit="save" class="card">
        <div class="form-group">
            <label>Dato</label>
            <input type="date" wire:model="picked_at" class="form-control">
            @error('picked_at')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Plukkested</label>
            <div id="wild-edible-picker" wire:ignore data-latitude="{{ $latitude }}" data-longitude="{{ $longitude }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map wild-edible-map-picker"></div>
            <input type="hidden" wire:model="latitude" id="wild-edible-latitude">
            <input type="hidden" wire:model="longitude" id="wild-edible-longitude">
            @error('latitude')<span class="text-danger">{{ $message }}</span>@enderror
            @error('longitude')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Kommentar</label>
            <textarea wire:model="comment" class="form-control"></textarea>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn btn-primary" type="submit">Gem plukning</button>
            <a class="btn btn-default" href="{{ route('wild-edibles.show', $wildEdible) }}" wire:navigate>Annuller</a>
        </div>
    </form>
</x-layouts.app-shell>
@push('scripts')
@if (config('wild-edibles.google_maps_api_key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ \urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>
@endif
@endpush
