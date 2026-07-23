@section('title', $species->common_name)
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
                                <a href="{{ route('species.add-preselected', $species) }}" class="btn btn-none">
                                    <i class="fa fa-plus"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <h1>{{ $species->common_name }}</h1>
                    <div class="tags">
                        <span style="font-style:italic;font-size:0.8rem;">{{ $species->scientific_name }}</span>
                        <div class="clear"></div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="notes">
                    <h1>Observations per Month</h1>
                    <div style="margin-top:20px;">
                        <canvas id="monthlyChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Observations table -->
                <div class="notes">
                    <h1>Observations ({{ $observations->total() }})</h1>
                    <div style="margin-top:20px;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Count</th>
                                    <th>Source</th>
                                    <th>Confidence</th>
                                    <th>Time</th>
                                    <th>Audio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($observations as $obs)
                                    <tr>
                                        <td>
                                            @php
                                                $dateStr = $obs->observed_at->format('Y-m-d') . ' ' . ($obs->observed_time ?? '00:00:00');
                                                $localTime = \Carbon\Carbon::parse($dateStr, 'UTC')
                                                    ->setTimezone('Europe/Copenhagen');
                                            @endphp
                                            {{ $localTime->format('d M Y H:i') }}
                                        </td>
                                        <td>{{ $obs->count }}</td>
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $observations->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
            <script>
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip();
                });
                const ctx = document.getElementById('monthlyChart').getContext('2d');
                const data = @json($monthlyData);
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(data),
                        datasets: [{
                            label: 'Observations',
                            data: Object.values(data),
                            backgroundColor: 'rgba(92, 184, 92, 0.6)',
                            borderColor: 'rgba(92, 184, 92, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });
            </script>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
