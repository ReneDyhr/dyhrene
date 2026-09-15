@section('title', 'Natur')
<x-layouts.app-shell :area="\App\Enums\AppArea::Nature">
    <div class="view-head">
        <h1>Natur</h1>
        <p>Hvad vi har set, hvor og hvornår. Observationer samles i artslisten efterhånden.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('observations.index') }}" wire:navigate>Observationer</a>
        <a href="{{ route('species.index') }}" wire:navigate>Arter</a>
        <a href="{{ route('wild-edibles.index') }}" wire:navigate>Vilde planter</a>
    </div>

    @if ($natureSnapshot !== null)
        <div class="stats">
            <div class="stat"><div class="n">{{ $natureSnapshot->speciesCount }}</div><div class="l">Arter</div></div>
            <div class="stat"><div class="n">{{ $natureSnapshot->observationCount }}</div><div class="l">Observationer i alt</div></div>
        </div>
    @endif

    <div class="section-title">Hvad er her nu — {{ \Carbon\Carbon::parse($date)->locale('da')->isoFormat('dddd D. MMMM YYYY') }}</div>

    <div x-data="{ open: false }" class="form-group">
        <button type="button" class="btn" @click="open = !open">
            <i class="fa fa-calendar" aria-hidden="true"></i>
            {{ \Carbon\Carbon::parse($date)->locale('da')->isoFormat('dddd D. MMMM YYYY') }}
        </button>
        <input
            type="date"
            x-cloak
            x-show="open"
            x-ref="picker"
            x-init="$watch('open', v => v && $nextTick(() => $refs.picker.focus()))"
            @change="open = false"
            wire:model.live="date"
            class="form-control"
            style="max-width: 220px; margin-top: 8px;"
        >
    </div>

    @if ($todaySummaries->isEmpty())
        <p>Ingen arter observeret på denne dato.</p>
    @else
        <div class="list">
            @foreach ($todaySummaries as $summary)
                @php
                    $species = $summary->species;
                    $audio = $speciesWithAudio[$species->id] ?? null;
                    $lastSeen = $summary->last_seen_at ? \Carbon\Carbon::parse($summary->last_seen_at, 'Europe/Copenhagen') : null;
                    $sources = $summary->sources_array;
                @endphp
                <div class="row">
                    <span class="swatch"></span>
                    <div>
                        <div class="lead">
                            <a href="{{ route('species.show', $species) }}" wire:navigate>{{ $species->common_name }}</a>
                        </div>
                        <div class="sub">
                            {{ $species->scientific_name }}
                            @if ($lastSeen) · senest {{ $lastSeen->format('H:i') }} @endif
                            · {{ $summary->windows_present }} vinduer i dag
                        </div>
                        @if (\count($sources) > 0)
                            <div class="taglist">
                                @if (\in_array('birdnet', $sources, true)) <span class="tag">BirdNET</span> @endif
                                @if (\in_array('ebird_import', $sources, true)) <span class="tag">eBird</span> @endif
                                @if (\in_array('manual', $sources, true)) <span class="tag">Manual</span> @endif
                            </div>
                        @endif
                    </div>
                    <div class="right">
                        @if ($audio && $audio['has_audio'])
                            <audio controls preload="none" style="width: 150px; height: 30px;">
                                <source src="{{ $audio['audio_url'] }}" type="audio/wav">
                            </audio>
                        @else
                            Ingen optagelse
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app-shell>
