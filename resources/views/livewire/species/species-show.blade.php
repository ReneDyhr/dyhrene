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
                            <li data-toggle="tooltip" data-placement="left" title="Nature dashboard">
                                <a href="{{ route('nature.dashboard') }}" wire:navigate class="btn btn-none">
                                    <i class="fa fa-leaf"></i>
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

            @if (session()->has('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

                <!-- Phenology strip -->
                <div class="notes">
                    <h1>Phenology</h1>
                    <p style="font-size:0.75rem; color:#888;">Windows present per day-of-year, 7-day rolling mean. Each line = one calendar year.</p>
                    <div style="margin-top:20px;">
                        <canvas id="phenologyChart" height="220"></canvas>
                    </div>
                </div>

                <!-- Diel activity heatmap -->
                <div class="notes">
                    <h1>Diel Activity</h1>
                    <p style="font-size:0.75rem; color:#888;">Raw detection counts by time relative to sunrise (S = sunrise, +/- hours) × month.</p>
                    <div style="margin-top:10px; overflow-x:auto;">
                        <svg id="dielHeatmap" viewBox="0 0 700 320" style="width:100%; max-width:700px; font-family: sans-serif;"></svg>
                    </div>
                </div>

                <!-- First/Last per year -->
                @if (!empty($firstLast))
                <div class="notes">
                    <h1>First &amp; Last Observations</h1>
                    <div style="margin-top:10px;">
                        <table class="table table-striped" style="font-size:0.8rem;">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>First seen</th>
                                    <th>Last seen</th>
                                    <th>Days present</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($firstLast as $fl)
                                <tr>
                                    <td>{{ $fl['year'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($fl['first'])->format('d M') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($fl['last'])->format('d M') }}</td>
                                    <td>{{ $fl['days'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Confidence distribution -->
                @if ($confidenceData['counts'] && array_sum($confidenceData['counts']) > 0)
                <div class="notes">
                    <h1>Confidence Distribution</h1>
                    <p style="font-size:0.7rem; color:#999;">BirdNET detections only. Data is station-filtered upstream.</p>
                    <div style="margin-top:20px;">
                        <canvas id="confidenceChart" height="160"></canvas>
                    </div>
                </div>
                @endif

                <!-- Observations table -->
                <div class="notes">
                    <h1>Observations ({{ $observations->total() }})</h1>
                    <div style="margin-top:20px;">
                        <table class="table table-striped" style="font-size:0.8rem;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Source</th>
                                    <th>Confidence</th>
                                    <th>Time</th>
                                    <th>Audio</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($observations as $obs)
                                    <tr>
                                        <td>
                                            @if ($obs->local_date)
                                                {{ \Carbon\Carbon::parse($obs->local_date)->format('d M Y') }}
                                            @else
                                                {{ $obs->observed_at->format('d M Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($obs->source?->is('ebird_import'))
                                                <span class="label label-info">eBird</span>
                                            @elseif ($obs->source?->is('birdnet'))
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
                                            @if ($obs->local_time)
                                                {{ substr($obs->local_time, 0, 5) }}
                                            @elseif ($obs->observed_time)
                                                {{ substr($obs->observed_time, 0, 5) }} UTC
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
                                                <audio controls preload="none" style="height: 28px; width: 150px;">
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
                        {{ $observations->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>

            <script>
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip();
                });

                // Phenology chart
                (function() {
                    const phenologyData = @json($phenologyData);
                    if (!phenologyData.series || phenologyData.series.length === 0) return;

                    const colors = ['#53875F', '#7BA68C', '#C44E52', '#4C72B0', '#DD8452', '#55A868'];
                    const datasets = phenologyData.series.map(function(s, i) {
                        return {
                            label: s.label,
                            data: s.data,
                            borderColor: colors[i % colors.length],
                            backgroundColor: 'transparent',
                            borderWidth: 1.5,
                            pointRadius: 0,
                            tension: 0.3
                        };
                    });

                    // X-axis labels: month markers at day-of-year boundaries
                    const monthDays = [0,31,59,90,120,151,181,212,243,273,304,334,365];
                    const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

                    const ctx = document.getElementById('phenologyChart').getContext('2d');
                    Chart.getChart(ctx)?.destroy();
                    new Chart(ctx, {
                        type: 'line',
                        data: { labels: phenologyData.days, datasets: datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 20, font: { size: 11 } } } },
                            scales: {
                                x: {
                                    title: { display: false },
                                    ticks: {
                                        callback: function(v) { return ''; },
                                        maxTicksLimit: 12
                                    },
                                    grid: { color: function(ctx) { return monthDays.includes(ctx.tick.value) ? '#ddd' : 'transparent'; } }
                                },
                                y: { beginAtZero: true, title: { display: true, text: 'Windows present (7-day avg)' } }
                            }
                        }
                    });
                })();

                // Diel heatmap SVG
                (function() {
                    const diel = @json($dielData);
                    if (!diel.data || diel.data.length === 0) return;

                    const svg = document.getElementById('dielHeatmap');
                    const cellW = 22, cellH = 22, leftPad = 50, topPad = 15, labelPad = 4;
                    const nRows = diel.data.length, nCols = diel.data[0].length;
                    const maxVal = Math.max(...diel.data.flat(), 1);
                    const width = leftPad + nCols * cellW + 10;
                    const height = topPad + nRows * cellH + 20;

                    svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);

                    let html = '';

                    // Column labels
                    for (let c = 0; c < nCols; c++) {
                        if (c % 4 === 0 || c === nCols - 1) {
                            html += '<text x="' + (leftPad + c * cellW + cellW/2) + '" y="' + (topPad - 4) + '" text-anchor="middle" font-size="8" fill="#666">' + diel.slots[c] + '</text>';
                        }
                    }

                    // Row labels + cells
                    for (let r = 0; r < nRows; r++) {
                        html += '<text x="' + (leftPad - 6) + '" y="' + (topPad + r * cellH + cellH/2 + 4) + '" text-anchor="end" font-size="9" fill="#666">' + diel.months[r] + '</text>';
                        for (let c = 0; c < nCols; c++) {
                            const v = diel.data[r][c];
                            const alpha = v / maxVal;
                            const g = Math.round(180 - alpha * 160);
                            html += '<rect x="' + (leftPad + c * cellW) + '" y="' + (topPad + r * cellH) + '" width="' + (cellW - 1) + '" height="' + (cellH - 1) + '" fill="rgb(' + g + ',' + (Math.round(255 - alpha * 80)) + ',' + g + ')" rx="2"><title>' + diel.months[r] + ' ' + diel.slots[c] + ': ' + v + ' detections</title></rect>';
                        }
                    }

                    svg.innerHTML = html;
                })();

                // Confidence histogram
                (function() {
                    const confData = @json($confidenceData);
                    if (!confData.bins || confData.bins.length === 0) return;
                    if (confData.counts.reduce(function(a,b) { return a+b; }, 0) === 0) return;

                    const ctx = document.getElementById('confidenceChart').getContext('2d');
                    Chart.getChart(ctx)?.destroy();
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: confData.bins,
                            datasets: [{
                                label: 'Detections',
                                data: confData.counts,
                                backgroundColor: 'rgba(83, 135, 95, 0.7)',
                                borderColor: '#53875F',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { title: { display: true, text: 'Confidence (5% bins, station-filtered upstream)' }, ticks: { maxTicksLimit: 11, font: { size: 9 } } },
                                y: { beginAtZero: true, title: { display: true, text: 'Detections' }, ticks: { stepSize: 1 } }
                            }
                        }
                    });
                })();
            </script>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
