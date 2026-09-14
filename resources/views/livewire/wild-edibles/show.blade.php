@section('title', $wildEdible->name)
<x-layouts.app-shell :area="\App\Enums\AppArea::Nature">
    <div class="content homepage"><div class="col-12"><div class="storage-list"><div class="recipe">
        <div style="display:flex;align-items:center;gap:10px"><h1 style="flex:1">{{ $wildEdible->name }}</h1><a class="btn btn-warning" href="{{ route('wild-edibles.edit', $wildEdible) }}">Edit</a><button class="btn btn-danger" wire:click="delete" wire:confirm="Delete this Wild Edible?">Delete</button></div>
        <div class="alert alert-info"><strong>Personal record:</strong> This map does not verify identification, edibility, safety, or legal access. You are responsible for safe and lawful foraging.</div>
        <p><strong>Type:</strong> {{ $wildEdible->type->label() }}<br><strong>Season:</strong> {{ $wildEdible->seasonLabel() }}<br><strong>Location:</strong> {{ $wildEdible->location_name ?: '—' }}<br><strong>Coordinates:</strong> {{ $wildEdible->latitude }}, {{ $wildEdible->longitude }}</p>
        @if($wildEdible->description)<p class="description">{{ $wildEdible->description }}</p>@endif
        <div id="wild-edible-detail-map" data-markers='@json($markers)' data-center-lat="{{ $wildEdible->latitude }}" data-center-lng="{{ $wildEdible->longitude }}" data-zoom="{{ config('wild-edibles.default_zoom') }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map"></div>
        @if($wildEdible->photos->isNotEmpty())<h2>Photos</h2><div style="display:flex;gap:15px;flex-wrap:wrap">@foreach($wildEdible->photos as $photo)<img src="{{ route('wild-edibles.photo', $photo) }}" alt="{{ $photo->original_file_name }}" style="max-width:260px;max-height:200px">@endforeach</div>@endif
        <h2>Picking history</h2><a class="btn btn-success btn-sm" href="{{ route('wild-edibles.pick', $wildEdible) }}">Add Picking</a>@if($wildEdible->pickings->isNotEmpty())<table class="table table-striped" style="margin-top:10px"><thead><tr><th>Date</th><th>Coordinates</th><th>Comment</th></tr></thead><tbody>@foreach($wildEdible->pickings as $picking)<tr><td>{{ $picking->picked_at->format('Y-m-d') }}</td><td>{{ $picking->latitude }}, {{ $picking->longitude }}</td><td>{{ $picking->comment ?: '—' }}</td></tr>@endforeach</tbody></table>@else<p class="text-muted">No pickings recorded yet.</p>@endif
        <p style="margin-top:20px"><a class="wild-edible-back-link" style="font-size:0.8rem !important;" href="{{ route('wild-edibles.index') }}">← Back to map</a></p>
    </div></div><div class="clear"></div></div></div>
</x-layouts.app-shell>
@push('scripts')
@if(config('wild-edibles.google_maps_api_key'))<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>@endif
@endpush
