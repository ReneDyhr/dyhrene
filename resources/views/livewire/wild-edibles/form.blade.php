<div>
    @section('title', $editing ? 'Edit Wild Edible' : 'Add Wild Edible')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage"><div class="col-12"><div class="storage-list"><div class="recipe">
            <h1>{{ $editing ? 'Edit Wild Edible' : 'Add Wild Edible' }}</h1>
            <div class="alert alert-info"><strong>Personal record:</strong> This map does not verify identification, edibility, safety, or legal access. You are responsible for safe and lawful foraging.</div>
            <form wire:submit="save">
                <div class="form-group"><label>Type</label><select wire:model="type" class="form-control">@foreach($types as $item)<option value="{{ $item->value }}">{{ $item->label() }}</option>@endforeach</select>@error('type')<span class="text-danger">{{ $message }}</span>@enderror</div>
                <div class="form-group"><label>Name</label><input wire:model="name" class="form-control">@error('name')<span class="text-danger">{{ $message }}</span>@enderror</div>
                <div class="form-group"><label>Description</label><textarea wire:model="description" class="form-control"></textarea>@error('description')<span class="text-danger">{{ $message }}</span>@enderror</div>
                <div class="form-group"><label>Location name</label><input wire:model="location_name" class="form-control"></div>
                <div class="form-group"><label>Location</label><div id="wild-edible-picker" data-latitude="{{ $latitude }}" data-longitude="{{ $longitude }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map wild-edible-map-picker"></div><input id="wild-edible-latitude" type="hidden" wire:model="latitude"><input id="wild-edible-longitude" type="hidden" wire:model="longitude">@error('latitude')<span class="text-danger">{{ $message }}</span>@enderror @error('longitude')<span class="text-danger">{{ $message }}</span>@enderror</div>
                <fieldset><legend>Season</legend><label><input type="checkbox" wire:model="season_all_year"> Available all year</label><div class="wild-edible-season-fields" style="margin-top:10px"><div><label>Start month</label><select wire:model="season_start_month" class="form-control" @disabled($season_all_year)><option value="">Unknown</option>@foreach(range(1,12) as $month)<option value="{{ $month }}">{{ date('F', mktime(0,0,0,$month,1)) }}</option>@endforeach</select></div><div><label>End month</label><select wire:model="season_end_month" class="form-control" @disabled($season_all_year)><option value="">Unknown</option>@foreach(range(1,12) as $month)<option value="{{ $month }}">{{ date('F', mktime(0,0,0,$month,1)) }}</option>@endforeach</select></div></div>@error('season_start_month')<span class="text-danger">{{ $message }}</span>@enderror</fieldset>
                <div class="form-group" style="margin-top:15px"><label>Photo (one image per upload)</label><input type="file" wire:model="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">@error('photo')<span class="text-danger">{{ $message }}</span>@enderror</div>
                @if($editing && $wildEdible->photos->isNotEmpty())<h3>Existing photos</h3><div style="display:flex;gap:12px;flex-wrap:wrap">@foreach($wildEdible->photos as $photo)<div><img src="{{ route('wild-edibles.photo', $photo) }}" alt="{{ $photo->original_file_name }}" style="max-width:180px;max-height:130px"><br><button type="button" wire:click="deletePhoto({{ $photo->id }})" class="btn btn-danger btn-xs" wire:confirm="Delete this photo?">Delete</button></div>@endforeach</div>@endif
                <div style="margin-top:20px"><button class="btn btn-success" type="submit">Save</button> <a class="btn btn-default" href="{{ $editing ? route('wild-edibles.show', $wildEdible) : route('wild-edibles.index') }}">Cancel</a></div>
            </form>
        </div></div><div class="clear"></div></div></div>
    </div>
</div>
@push('scripts')
@if(config('wild-edibles.google_maps_api_key'))<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>@endif
@endpush
