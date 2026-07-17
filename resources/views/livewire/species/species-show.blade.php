<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('species.index') }}" wire:navigate class="text-blue-600">&larr; Back</a>
            <h2 class="font-semibold text-xl">{{ $species->common_name }}</h2>
            <span class="text-gray-500 italic">{{ $species->scientific_name }}</span>
        </div>
    </x-slot>

    <div class="p-4 space-y-6">
        <div class="border rounded p-4">
            <h3 class="font-semibold mb-2">Observations per Month</h3>
            <canvas id="monthlyChart" height="200"></canvas>
        </div>

        <div class="border rounded">
            <h3 class="font-semibold p-3 border-b">Observations ({{ count($observations) }})</h3>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 text-left">Date</th>
                        <th class="p-2 text-left">Time</th>
                        <th class="p-2 text-left">Count</th>
                        <th class="p-2 text-left">Location</th>
                        <th class="p-2 text-left">Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($observations as $obs)
                        <tr class="border-t">
                            <td class="p-2">{{ $obs->observed_at->format('d M Y') }}</td>
                            <td class="p-2">{{ $obs->observed_time ?? '—' }}</td>
                            <td class="p-2">{{ $obs->count }}</td>
                            <td class="p-2">{{ $obs->location ?? '—' }}</td>
                            <td class="p-2">
                                @if ($obs->source === 'ebird_import')
                                    <span class="text-xs bg-blue-100 text-blue-700 px-1 rounded">eBird</span>
                                @else
                                    <span class="text-xs bg-gray-100 px-1 rounded">Manual</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const data = @json($monthlyData);
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'Observations',
                    data: Object.values(data),
                    backgroundColor: 'rgba(34, 139, 34, 0.6)',
                    borderColor: 'rgba(34, 139, 34, 1)',
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
    @endpush
</div>
