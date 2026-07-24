@section('title', 'Nature — What\'s Here Now')
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content recipe-page">
            <div class="col-12">
                <div class="recipe">
                    <h1>What is Here Now</h1>
                    <div class="tags">
                        <span style="font-size:0.8rem;">{{ \now('Europe/Copenhagen')->format('l, d F Y') }}</span>
                        <div class="clear"></div>
                    </div>
                </div>

                @if ($todaySummaries->isEmpty())
                    <div class="notes">
                        <p>No species observed today yet.</p>
                    </div>
                @else
                    <div class="notes">
                        <h1>{{ $todaySummaries->count() }} species today</h1>
                        <div style="margin-top:20px;">
                            @foreach ($todaySummaries as $summary)
                                @php
                                    $species = $summary->species;
                                    $audio = $speciesWithAudio[$species->id] ?? null;
                                    $lastSeen = $summary->last_seen_at
                                        ? \Carbon\Carbon::parse($summary->last_seen_at, 'Europe/Copenhagen')
                                        : null;
                                    $sources = $summary->sources_array;
                                @endphp
                                <div style="display: flex; flex-wrap: wrap; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                                    <div style="flex: 1 1 300px; min-width: 0;">
                                        <h3 style="margin-top: 0; margin-bottom: 2px;">
                                            <a href="{{ route('species.show', $species) }}" wire:navigate style="text-decoration: none;">
                                                {{ $species->common_name }}
                                            </a>
                                        </h3>
                                        <p style="font-style: italic; color: #666; margin-bottom: 5px; font-size: 0.85rem;">
                                            {{ $species->scientific_name }}
                                        </p>
                                        <p style="font-size: 0.8rem; color: #999; margin-bottom: 0;">
                                            @if ($lastSeen)
                                                Last seen: {{ $lastSeen->format('H:i') }}
                                            @endif
                                            &nbsp;·&nbsp;
                                            {{ $summary->windows_present }} windows today
                                        </p>
                                        <div style="margin-top: 5px;">
                                            @if (\in_array('birdnet', $sources, true))
                                                <span class="label label-success" style="font-size: 0.7rem;">BirdNET</span>
                                            @endif
                                            @if (\in_array('ebird_import', $sources, true))
                                                <span class="label label-info" style="font-size: 0.7rem;">eBird</span>
                                            @endif
                                            @if (\in_array('manual', $sources, true))
                                                <span class="label label-default" style="font-size: 0.7rem;">Manual</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="flex: 1 1 150px; min-width: 0; align-self: center;">
                                        @if ($audio && $audio['has_audio'])
                                            <audio controls preload="none" style="width: 100%; height: 30px;">
                                                <source src="{{ $audio['audio_url'] }}" type="audio/wav">
                                            </audio>
                                        @else
                                            <span style="font-size: 0.75rem; color: #bbb;">No recording</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
