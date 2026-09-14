@section('title', 'Kvitteringer')
<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <div class="view-head">
        <h1>Kvitteringer</h1>
        <p>Dine kvitteringer, sorteret efter måned.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('receipts.create') }}" wire:navigate class="cta" style="margin-top: 0;">+ Ny kvittering</a>
        <a href="{{ route('receipts.mass-edit-items') }}" wire:navigate>Masse-redigering</a>
    </div>

    @php $currentMonth = \now()->format('Y-m'); @endphp

    @foreach ($receiptsByMonth as $monthData)
        <section x-data="{ open: @json($monthData['month'] === $currentMonth) }">
            <button type="button" class="month-toggle" @click="open = !open" :aria-expanded="open">
                <span>{{ $monthData['monthName'] }} · {{ $monthData['count'] }} {{ $monthData['count'] === 1 ? 'kvittering' : 'kvitteringer' }} · {{ \App\Support\Format::number($monthData['total']) }} {{ $monthData['currency'] === 'EUR' ? '€' : 'kr.' }}</span>
                <span class="month-toggle__line"></span>
                <span class="month-toggle__chevron" aria-hidden="true" x-text="open ? '▾' : '▸'"></span>
            </button>

            <div x-show="open">
                <div class="list">
                    @foreach ($monthData['receipts'] as $receipt)
                        <div class="row">
                            <span class="swatch"></span>
                            <div>
                                <div class="lead"><a href="{{ route('receipts.show', $receipt) }}" wire:navigate>{{ $receipt->name }}</a></div>
                                <div class="sub">
                                    {{ $receipt->items->count() }} varer · {{ $receipt->date->locale('da')->isoFormat('D. MMM YYYY') }}
                                </div>
                            </div>
                            <div class="right">
                                {{ $receipt->currency === 'EUR' ? \App\Support\Format::number($receipt->total) . ' €' : \App\Support\Format::dkk($receipt->total) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    @if ($receiptsByMonth->isEmpty())
        <p>Ingen kvitteringer endnu.</p>
    @endif
</x-layouts.app-shell>
