@section('title', $title)
<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <div class="view-head">
        <h1>{{ $title }}</h1>
        <p>{{ \count($receipts) }} kvitteringer</p>
    </div>

    <a class="cta" href="{{ route('receipts.create') }}" wire:navigate style="margin-top: 0; margin-bottom: 24px;">
        <i class="fa fa-plus" aria-hidden="true"></i> Tilføj kvittering
    </a>

    @if (\count($receipts) > 0)
        <div class="list">
            @foreach ($receipts as $receipt)
                <a class="row" href="{{ route('receipts.show', $receipt->id) }}" wire:navigate>
                    <span class="swatch"></span>
                    <div>
                        <div class="lead">{{ $receipt->name }}</div>
                        <div class="sub">
                            @if ($receipt->vendor){{ $receipt->vendor }} · @endif{{ $receipt->items->count() }} varer · {{ $receipt->date?->locale('da')?->isoFormat('D. MMM YYYY') ?? '' }}
                        </div>
                    </div>
                    <div class="right">
                        {{ $receipt->currency === 'EUR' ? \App\Support\Format::number($receipt->total) . ' €' : \App\Support\Format::dkk($receipt->total) }}
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p>Ingen kvitteringer fundet.</p>
    @endif
</x-layouts.app-shell>
