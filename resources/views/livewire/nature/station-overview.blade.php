@section('title', 'Station Overview')
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content recipe-page">
            <div class="col-12">
                <div class="recipe">
                    <div class="actions">
                        <ul>
                            <li data-toggle="tooltip" data-placement="left" title="Nature dashboard">
                                <a href="{{ route('nature.dashboard') }}" wire:navigate class="btn btn-none">
                                    <i class="fa fa-arrow-left"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <h1>Station Overview</h1>
                </div>

                <!-- Species accumulation curve -->
                <div class="notes">
                    <h1>Species Accumulation</h1>
                    <p style="font-size:0.75rem; color:#888;">Cumulative unique species over time. New upward steps = something genuinely arrived.</p>
                    <div style="margin-top:20px;">
                        <canvas id="accumulationChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Source breakdown -->
                <div class="notes">
                    <h1>Source Breakdown</h1>
                    <p style="font-size:0.75rem; color:#888;">How much comes from the microphone vs. walking around.</p>
                    <div style="margin-top:20px; max-width:350px; margin-left:auto; margin-right:auto;">
                        <canvas id="sourceChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Calendar heatmap -->
                @php $cal = $calendarData; @endphp
                <div class="notes">
                    <h1>Observation Calendar — {{ $cal['year'] }}</h1>
                    <p style="font-size:0.7rem; color:#888;">GitHub-style heatmap. Each cell = one local day. Intensity = unique species observed. Empty = genuinely quiet day.</p>
                    <div style="margin-top:10px; overflow-x:auto;">
                        @if (!empty($cal['weeks']))
                        <svg id="calendarHeatmap" viewBox="0 0 750 130" style="width:100%; max-width:750px; font-family: sans-serif; min-width: 600px;"></svg>
                        @else
                        <p>No data for {{ $cal['year'] }}.</p>
                        @endif
                    </div>
                </div>

                <!-- Month view -->
                <div class="notes">
                    <h1>Month View — {{ $selectedYear }}</h1>
                    <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:3px;">
                        @php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
                        @foreach ($months as $i => $name)
                            <button wire:click="selectMonth({{ $i + 1 }})" class="btn btn-sm {{ $selectedMonth === $i + 1 ? 'btn-success' : 'btn-default' }}" style="font-size:0.75rem;">{{ $name }}</button>
                        @endforeach
                        @if ($selectedMonth !== null)
                            <button wire:click="clearMonth" class="btn btn-sm btn-default" style="font-size:0.75rem;">Clear</button>
                        @endif
                    </div>

                    @if ($selectedMonth !== null && !empty($monthViewData))
                    <div style="margin-top:15px;">
                        <table class="table table-striped" style="font-size:0.8rem;">
                            <thead>
                                <tr>
                                    <th>Species</th>
                                    <th>Windows present</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monthViewData as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('species.show', $row['species_id']) }}" wire:navigate>
                                            {{ $row['common_name'] }}
                                        </a>
                                        <span style="font-style:italic; font-size:0.7rem; color:#888; display:block;">{{ $row['scientific_name'] }}</span>
                                    </td>
                                    <td>{{ $row['windows'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif ($selectedMonth !== null)
                        <p style="margin-top:10px;">No species data for this month.</p>
                    @endif
                </div>
            </div>

            <script>
                // Accumulation curve
                (function() {
                    const acc = @json($accumulationData);
                    if (!acc.labels || acc.labels.length === 0) return;
                    const ctx = document.getElementById('accumulationChart').getContext('2d');
                    Chart.getChart(ctx)?.destroy();
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: acc.labels,
                            datasets: [{
                                label: 'Species',
                                data: acc.data,
                                borderColor: '#53875F',
                                backgroundColor: 'rgba(83,135,95,0.1)',
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 0,
                                tension: 0.2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { maxTicksLimit: 12, font: { size: 9 } } },
                                y: { beginAtZero: true, title: { display: true, text: 'Cumulative species' } }
                            }
                        }
                    });
                })();

                // Source breakdown
                (function() {
                    const src = @json($sourceBreakdown);
                    const ctx = document.getElementById('sourceChart').getContext('2d');
                    Chart.getChart(ctx)?.destroy();
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: src.labels,
                            datasets: [{
                                data: src.data,
                                backgroundColor: ['#53875F', '#4C72B0', '#999'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } }
                        }
                    });
                })();

                // Calendar heatmap
                @if (!empty($cal['weeks']))
                (function() {
                    const cal = @json($cal);
                    const svg = document.getElementById('calendarHeatmap');
                    const cellW = 13, cellH = 13, leftPad = 28, topPad = 18;
                    const nCols = cal.weeks.length;
                    const width = leftPad + nCols * cellW + 20;
                    const height = topPad + 7 * cellH + 10;
                    svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);

                    let html = '';
                    const dayLabels = ['','M','','W','','F',''];

                    // Day labels
                    for (let d = 0; d < 7; d++) {
                        if (dayLabels[d]) {
                            html += '<text x="' + (leftPad - 8) + '" y="' + (topPad + d * cellH + cellH - 3) + '" text-anchor="end" font-size="8" fill="#666">' + dayLabels[d] + '</text>';
                        }
                    }

                    // Month labels
                    let lastMonth = 0;
                    for (let w = 0; w < cal.weeks.length; w++) {
                        const week = cal.weeks[w];
                        for (let d = 0; d < 7; d++) {
                            const cell = week[d];
                            if (cell && cell.month !== lastMonth && d <= 3) {
                                html += '<text x="' + (leftPad + w * cellW + 2) + '" y="' + (topPad - 5) + '" font-size="8" fill="#666">' + cal.months[cell.month - 1] + '</text>';
                                lastMonth = cell.month;
                                break;
                            }
                        }
                    }

                    // Cells
                    const maxVal = Math.max(...cal.weeks.flat().map(function(c) { return c.count; }), 1);
                    for (let w = 0; w < cal.weeks.length; w++) {
                        for (let d = 0; d < 7; d++) {
                            const cell = cal.weeks[w][d];
                            if (!cell || cell.count < 0) {
                                html += '<rect x="' + (leftPad + w * cellW) + '" y="' + (topPad + d * cellH) + '" width="' + (cellW-1) + '" height="' + (cellH-1) + '" fill="#f0f0f0" rx="2"></rect>';
                            } else {
                                const alpha = cell.count / maxVal;
                                const r = Math.round(200 - alpha * 170);
                                const g = Math.round(220 - alpha * 170);
                                html += '<rect x="' + (leftPad + w * cellW) + '" y="' + (topPad + d * cellH) + '" width="' + (cellW-1) + '" height="' + (cellH-1) + '" fill="rgb(' + r + ',' + g + ',' + r + ')" rx="2"><title>' + cell.date + ': ' + cell.count + ' species</title></rect>';
                            }
                        }
                    }

                    svg.innerHTML = html;
                })();
                @endif
            </script>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
