<div>
    @section('title', 'Wild Edibles')
    @include('components.layouts.sidenav')
    <div id="main">@include('components.layouts.header')<div class="content homepage"><div class="col-12"><div class="storage-list"><div class="recipe">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap"><h1 style="flex:1">Wild Edibles</h1><a class="btn btn-success" href="{{ route('wild-edibles.create') }}"><i class="fa fa-plus"></i> Add Wild Edible</a></div>
        <div class="alert alert-info"><strong>Personal record:</strong> This map does not verify identification, edibility, safety, or legal access. You are responsible for safe and lawful foraging.</div>
        <div class="wild-edible-filters"><strong>Types:</strong> @foreach($typesList as $type)<label><input type="checkbox" wire:model.live="types" value="{{ $type->value }}"> {{ $type->label() }}</label>@endforeach <strong style="margin-left:15px">Ready in:</strong> @foreach($monthsList as $month)<label><input type="checkbox" wire:model.live="months" value="{{ $month }}"> {{ date('M', mktime(0,0,0,$month,1)) }}</label>@endforeach <button type="button" wire:click="clearFilters" class="btn btn-default btn-xs">Clear</button></div>
        @if(!config('wild-edibles.google_maps_api_key'))<div class="alert alert-warning">Google Maps is not configured. Set <code>GOOGLE_MAPS_API_KEY</code> to enable the private map.</div>@endif
        <div id="wild-edibles-map" data-markers='@json($markers)' data-center-lat="{{ config('wild-edibles.default_center.latitude') }}" data-center-lng="{{ config('wild-edibles.default_center.longitude') }}" data-zoom="{{ config('wild-edibles.default_zoom') }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map"></div>
        <div class="wild-edible-legend"><strong>Legend:</strong> @foreach($typesList as $type)<span><i aria-hidden="true" style="background:{{ $type->color() }}">{{ $type->markerIcon() }}</i>{{ $type->label() }}</span>@endforeach</div>
        @if($edibles->isEmpty())<div class="alert alert-info">No Wild Edibles match the current filters.</div>@else<table class="table table-striped"><thead><tr><th>Name</th><th>Type</th><th>Season</th><th>Location</th><th>Actions</th></tr></thead><tbody>@foreach($edibles as $edible)<tr><td>{{ $edible->name }}</td><td>{{ $edible->type->label() }}</td><td>{{ $edible->seasonLabel() }}</td><td>{{ $edible->location_name ?: '—' }}</td><td><a class="btn btn-info btn-xs" href="{{ route('wild-edibles.show', $edible) }}">View</a> <a class="btn btn-warning btn-xs" href="{{ route('wild-edibles.edit', $edible) }}">Edit</a> <button class="btn btn-danger btn-xs" wire:click="delete({{ $edible->id }})" wire:confirm="Delete this Wild Edible?">Delete</button></td></tr>@endforeach</tbody></table>@endif
    </div></div><div class="clear"></div></div></div></div>
</div>
@push('scripts')
@if(config('wild-edibles.google_maps_api_key'))<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>@endif
@endpush
