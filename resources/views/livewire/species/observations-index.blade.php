@section('title', 'All Observations')
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content recipe-page">
            <div class="col-12">
                <div class="recipe">
                    <div class="actions">
                        <ul>
                            <li data-toggle="tooltip" data-placement="left" title="Back to species list">
                                <a href="{{ route('species.index') }}" wire:navigate class="btn btn-none">
                                    <i class="fa fa-arrow-left"></i>
                                </a>
                            </li>
                            <li data-toggle="tooltip" data-placement="left" title="Log new observation">
                                <a href="{{ route('species.add') }}" class="btn btn-none">
                                    <i class="fa fa-plus"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <h1>All Observations</h1>
                </div>

                @if (session()->has('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="notes">
                    <h1>Observations</h1>
                    <div style="margin-top:20px;">
                        <table class="table table-striped" style="font-size: 0.8rem;">
                            <thead>
                                <tr>
                                    <th style="font-size: 0.8rem;">
                                        <a href="#" wire:click.prevent="sortBy('common_name')" style="color: inherit; text-decoration: none; font-size: 0.8rem;">
                                            Species
                                            @if ($sortField === 'common_name')
                                                <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                                            @else
                                                <i class="fa fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th style="font-size: 0.8rem;">
                                        <a href="#" wire:click.prevent="sortBy('observed_at')" style="color: inherit; text-decoration: none; font-size: 0.8rem;">
                                            Date
                                            @if ($sortField === 'observed_at')
                                                <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                                            @else
                                                <i class="fa fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th style="font-size: 0.8rem;">
                                        <a href="#" wire:click.prevent="sortBy('source')" style="color: inherit; text-decoration: none; font-size: 0.8rem;">
                                            Source
                                            @if ($sortField === 'source')
                                                <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                                            @else
                                                <i class="fa fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th style="font-size: 0.8rem;">Confidence</th>
                                    <th style="font-size: 0.8rem;">Time</th>
                                    <th style="font-size: 0.8rem;">Audio</th>
                                    <th style="font-size: 0.8rem;">Actions</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.8rem;">
                                @foreach ($observations as $obs)
                                    <tr>
                                        <td>
                                            <a href="{{ route('species.show', $obs->species) }}" wire:navigate style="font-size: 0.8rem;">
                                                {{ $obs->species->common_name }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $dateStr = $obs->observed_at->format('Y-m-d') . ' ' . ($obs->observed_time ?? '00:00:00');
                                                $localTime = \Carbon\Carbon::parse($dateStr, 'UTC')
                                                    ->setTimezone('Europe/Copenhagen');
                                            @endphp
                                            {{ $localTime->format('d M Y H:i') }}
                                        </td>
                                        <td>
                                            @if ($obs->source === 'ebird_import')
                                                <span class="label label-info">eBird</span>
                                            @elseif ($obs->source === 'birdnet')
                                                <span class="label label-success">BirdNET</span>
                                            @else
                                                <span class="label label-default">Manual</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $maxConf = $obs->birdnetDetections->isNotEmpty()
                                                    ? $obs->birdnetDetections->max('confidence')
                                                    : null;
                                            @endphp
                                            @if ($maxConf !== null)
                                                <span title="{{ number_format((float) $maxConf * 100, 2) }}%">
                                                    {{ number_format((float) $maxConf * 100, 1) }}%
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $minStart = $obs->birdnetDetections->isNotEmpty()
                                                    ? $obs->birdnetDetections->min('start_time')
                                                    : null;
                                                $maxEnd = $obs->birdnetDetections->isNotEmpty()
                                                    ? $obs->birdnetDetections->max('end_time')
                                                    : null;
                                            @endphp
                                            @if ($minStart !== null && $maxEnd !== null)
                                                {{ number_format((float) $minStart, 1) }}s – {{ number_format((float) $maxEnd, 1) }}s
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $detection = $obs->birdnetDetections->first(fn ($d) => $d->audio_path);
                                                $audioUrl = $detection?->audioUrl();
                                            @endphp
                                            @if ($audioUrl)
                                                <audio controls preload="none" style="height: 28px; width: 200px;">
                                                    <source src="{{ $audioUrl }}" type="audio/wav">
                                                </audio>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <button
                                                class="btn btn-danger btn-sm"
                                                wire:click="delete({{ $obs->id }})"
                                                wire:confirm="Are you sure you want to delete this observation?"
                                                data-toggle="tooltip"
                                                data-placement="left"
                                                title="Delete observation"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:20px;">
                        {{ $observations->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>

            <script>
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip();
                });
            </script>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
