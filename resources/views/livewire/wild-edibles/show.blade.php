@section('title', $wildEdible->name)
<x-layouts.app-shell :area="\App\Enums\AppArea::Nature">
    <div class="view-head">
        <h1>{{ $wildEdible->name }}</h1>
        <p>{{ $wildEdible->type->label() }} · {{ $wildEdible->seasonLabel() }}</p>
    </div>

    <article class="card">
        <div class="alert alert-info"><strong>Personlig notat:</strong> Kortet verificerer ikke identifikation, spiselighed, sikkerhed eller adgang. Du er ansvarlig for sikker og lovlig sankning.</div>

        <div class="meta" style="margin-bottom: 12px;">
            <p style="margin: 0;">Type: {{ $wildEdible->type->label() }}</p>
            <p style="margin: 0;">Sæson: {{ $wildEdible->seasonLabel() }}</p>
            <p style="margin: 0;">Sted: {{ $wildEdible->location_name ?: '—' }}</p>
            <p style="margin: 0;">Koordinater: {{ $wildEdible->latitude }}, {{ $wildEdible->longitude }}</p>
        </div>

        @if ($wildEdible->description)
            <p>{{ $wildEdible->description }}</p>
        @endif

        <div id="wild-edible-detail-map" wire:ignore data-markers='@json($markers)' data-center-lat="{{ $wildEdible->latitude }}" data-center-lng="{{ $wildEdible->longitude }}" data-zoom="{{ config('wild-edibles.default_zoom') }}" data-map-id="{{ config('wild-edibles.google_maps_map_id') }}" class="wild-edible-map"></div>

        <div style="display: flex; gap: 10px; margin-top: 16px;">
            <a href="{{ route('wild-edibles.edit', $wildEdible) }}" class="btn btn-primary" wire:navigate>Redigér</a>
            <button class="btn btn-danger" wire:click="delete" wire:confirm="Slet denne vilde plante?">Slet</button>
        </div>
    </article>

    @if ($wildEdible->photos->isNotEmpty())
        <div class="section-title">Billeder</div>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            @foreach ($wildEdible->photos as $photo)
                <img src="{{ route('wild-edibles.photo', $photo) }}" alt="{{ $photo->original_file_name }}" style="max-width: 260px; max-height: 200px;">
            @endforeach
        </div>
    @endif

    <div class="section-title">Plukkehistorik</div>
    <a href="{{ route('wild-edibles.pick', $wildEdible) }}" class="cta" wire:navigate>+ Tilføj plukning</a>
    @if ($wildEdible->pickings->isNotEmpty())
        <div class="list" style="margin-top: 12px;">
            @foreach ($wildEdible->pickings as $picking)
                <div class="row">
                    <span class="swatch"></span>
                    <div>
                        <div class="lead">{{ $picking->picked_at->format('Y-m-d') }}</div>
                        <div class="sub">{{ $picking->latitude }}, {{ $picking->longitude }}</div>
                    </div>
                    <div class="right">{{ $picking->comment ?: '—' }}</div>
                </div>
            @endforeach
        </div>
    @else
        <p>Ingen plukninger endnu.</p>
    @endif

    <p style="margin-top: 20px;">
        <a href="{{ route('wild-edibles.index') }}" wire:navigate>← Tilbage til kortet</a>
    </p>
</x-layouts.app-shell>
@push('scripts')
@if (config('wild-edibles.google_maps_api_key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ \urlencode((string) config('wild-edibles.google_maps_api_key')) }}&libraries=marker&callback=__wildEdiblesGoogleMapsReady" async defer></script>
@endif
@endpush
