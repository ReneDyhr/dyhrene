@section('title', 'Vilde planter')
<x-layouts.app-shell :area="\App\Enums\AppArea::Nature">
    <div class="view-head">
        <h1>Vilde planter</h1>
        <p>Kort over vilde planter, og hvor de kan findes.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('wild-edibles.create') }}" wire:navigate class="cta" style="margin-top: 0;">+ Tilføj vild plante</a>
    </div>

    <article class="card">
        <div class="alert alert-info"><strong>Personlig notat:</strong> Kortet verificerer ikke identifikation, spiselighed, sikkerhed eller adgang. Du er ansvarlig for sikker og lovlig sankning.</div>

        <div class="wild-edible-filters">
            <strong>Typer:</strong>
            @foreach ($typesList as $type)
                <label><input type="checkbox" wire:model.live="types" value="{{ $type->value }}"> {{ $type->label() }}</label>
            @endforeach
            <strong style="margin-left: 15px;">Sæson:</strong>
            @foreach ($monthsList as $month)
                <label><input type="checkbox" wire:model.live="months" value="{{ $month }}"> {{ \date('M', \mktime(0, 0, 0, $month, 1)) }}</label>
            @endforeach
            <button type="button" wire:click="clearFilters" class="btn btn-default btn-xs">Ryd</button>
        </div>

        @if (!config('wild-edibles.google_maps_api_key'))
            <div class="alert alert-warning">Google Maps er ikke konfigureret. Sæt <code>GOOGLE_MAPS_API_KEY</code> for at aktivere kortet.</div>
        @endif

        <div id="wild-edibles-map" wire:ignore data-markers='@json($markers)' data-center-lat="{{ config('wild-edibles.default_center.latitude') }}" data-center-lng="{{ config('wild-edibles.default_center.longitude') }}" data-zoom="{{ config('wild-edibles.default_zoom') }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map"></div>

        <div class="wild-edible-legend">
            <strong>Forklaring:</strong>
            @foreach ($typesList as $type)
                <span><i aria-hidden="true" style="background: {{ $type->color() }}">{{ $type->markerIcon() }}</i>{{ $type->label() }}</span>
            @endforeach
        </div>
    </article>

    <div class="section-title">Planter</div>
    @if ($edibles->isEmpty())
        <div class="alert alert-info">Ingen vilde planter matcher filtrene.</div>
    @else
        <div class="list">
            @foreach ($edibles as $edible)
                <div class="row">
                    <span class="swatch"></span>
                    <div>
                        <div class="lead"><a href="{{ route('wild-edibles.show', $edible) }}" wire:navigate>{{ $edible->name }}</a></div>
                        <div class="sub">{{ $edible->type->label() }} · {{ $edible->seasonLabel() }} · {{ $edible->location_name ?: '—' }}</div>
                    </div>
                    <div class="right">
                        <span class="actions">
                            <a href="{{ route('wild-edibles.edit', $edible) }}" wire:navigate>Redigér</a>
                            <a href="#" class="danger" wire:confirm="Slet denne vilde plante?" wire:click.prevent="delete({{ $edible->id }})">Slet</a>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app-shell>
@push('scripts')
@if (config('wild-edibles.google_maps_api_key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ \urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>
@endif
@endpush
