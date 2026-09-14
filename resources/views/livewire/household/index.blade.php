<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <div class="view-head">
        <h1>Hus</h1>
        <p>Kvitteringer, inventar og det praktiske. Gemt så det kan findes igen, ikke bare gemt væk.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('receipts.index') }}" wire:navigate aria-current="page">Kvitteringer</a>
        <a href="{{ route('inventory.index') }}" wire:navigate>Inventar</a>
        <a href="{{ route('inventory.categories') }}" wire:navigate>Inventarkategorier</a>
        <a href="{{ route('receipts.create') }}" wire:navigate>Tilføj kvittering</a>
    </div>

    <div class="stats">
        <div class="stat"><div class="n">{{ $snapshot->receipts->currentMonthCount }}</div><div class="l">Kvitteringer denne måned</div></div>
        <div class="stat"><div class="n">{{ $snapshot->inventory->count }}</div><div class="l">Genstande i inventar</div></div>
    </div>

    <div class="section-title">Seneste kvitteringer</div>
    @if (\count($snapshot->latestReceipts) > 0)
        <div class="list">
            @foreach ($snapshot->latestReceipts as $receipt)
                <a class="row" href="{{ route('receipts.show', $receipt->id) }}" wire:navigate>
                    <span class="swatch"></span>
                    <div>
                        <div class="lead">{{ $receipt->name }}</div>
                        <div class="sub">{{ $receipt->category }} · {{ $receipt->itemCount }} varer</div>
                    </div>
                    <div class="right">
                        {{ $receipt->currency === 'EUR' ? \App\Support\Format::number($receipt->amount) . ' €' : \App\Support\Format::dkk($receipt->amount) }}<br>
                        <span class="sub">{{ \Carbon\Carbon::parse($receipt->date)->locale('da')->isoFormat('D. MMM') }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p>Ingen kvitteringer endnu.</p>
    @endif
</x-layouts.app-shell>
