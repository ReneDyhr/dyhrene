@section('title', 'Add Picking')
<x-layouts.app-shell :area="\App\Enums\AppArea::Nature">
    <div class="content homepage"><div class="col-12"><div class="storage-list"><div class="recipe">
        <h1>Add Picking: {{ $wildEdible->name }}</h1>
        <div class="alert alert-info"><strong>Personal record:</strong> This map does not verify identification, edibility, safety, or legal access. You are responsible for safe and lawful foraging.</div>
        <form wire:submit="save">
            <div class="form-group"><label>Date</label><input type="date" wire:model="picked_at" class="form-control">@error('picked_at')<span class="text-danger">{{ $message }}</span>@enderror</div>
            <div class="form-group"><label>Picking location</label><div id="wild-edible-picker" data-latitude="{{ $latitude }}" data-longitude="{{ $longitude }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map wild-edible-map-picker"></div><input type="hidden" wire:model="latitude" id="wild-edible-latitude"><input type="hidden" wire:model="longitude" id="wild-edible-longitude">@error('latitude')<span class="text-danger">{{ $message }}</span>@enderror @error('longitude')<span class="text-danger">{{ $message }}</span>@enderror</div>
            <div class="form-group"><label>Comment</label><textarea wire:model="comment" class="form-control"></textarea></div>
            <button class="btn btn-success">Save Picking</button> <a class="btn btn-default" href="{{ route('wild-edibles.show', $wildEdible) }}">Cancel</a>
        </form>
    </div></div><div class="clear"></div></div></div>
</x-layouts.app-shell>
@push('scripts')
@if(config('wild-edibles.google_maps_api_key'))<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>@endif
@endpush
