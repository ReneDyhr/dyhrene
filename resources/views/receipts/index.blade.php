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

    @foreach ($receiptsByMonth as $monthData)
        <div class="section-title">
            {{ $monthData['monthName'] }} · {{ \App\Support\Format::number($monthData['total']) }} {{ $monthData['currency'] === 'EUR' ? '€' : 'kr.' }}
        </div>

        <div class="list">
            @foreach ($monthData['receipts'] as $receipt)
                <div class="row">
                    <span class="swatch"></span>
                    <div>
                        <div class="lead"><a href="{{ route('receipts.show', $receipt) }}" wire:navigate>{{ $receipt->name }}</a></div>
                        <div class="sub">
                            @if ($receipt->vendor){{ $receipt->vendor }} · @endif{{ $receipt->items->count() }} varer · {{ $receipt->date->locale('da')->isoFormat('D. MMM YYYY') }}
                        </div>
                    </div>
                    <div class="right">
                        {{ $receipt->currency === 'EUR' ? \App\Support\Format::number($receipt->total) . ' €' : \App\Support\Format::dkk($receipt->total) }}<br>
                        <span class="sub">
                            <a href="{{ route('receipts.edit', $receipt) }}" wire:navigate>Redigér</a>
                            <a href="#" wire:confirm="Er du sikker?" wire:click.prevent="deleteReceipt({{ $receipt->id }})">· Slet</a>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    @if ($receiptsByMonth->isEmpty())
        <p>Ingen kvitteringer endnu.</p>
    @endif
</x-layouts.app-shell>
