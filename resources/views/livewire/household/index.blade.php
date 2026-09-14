<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <div class="view-head">
        <h1>Husholdning</h1>
        <p>Kvitteringer, inventar og det praktiske. Gemt så det kan findes igen, ikke bare gemt væk.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('receipts.index') }}" wire:navigate aria-current="page">Kvitteringer</a>
        <a href="{{ route('inventory.index') }}" wire:navigate>Inventar</a>
        <a href="{{ route('inventory.categories') }}" wire:navigate>Inventarkategorier</a>
    </div>

    <div class="stats">
        <div class="stat"><div class="n">{{ $snapshot->receipts->currentMonthCount }}</div><div class="l">Kvitteringer denne måned</div></div>
        <div class="stat"><div class="n">{{ $snapshot->inventory->count }}</div><div class="l">Genstande i inventar</div></div>
    </div>

    @if ($snapshot->receipts->latest !== null)
        <div class="section-title">Nyeste kvittering</div>
        <div class="list">
            <div class="row">
                <span class="swatch" style="--theme: var(--husholdning)"></span>
                <div>
                    <div class="lead">{{ $snapshot->receipts->latest->name }}</div>
                    <div class="sub">Senest registreret</div>
                </div>
                <a class="right" href="{{ route('receipts.index') }}">Åbn →</a>
            </div>
        </div>
    @endif

    <a class="cta" href="{{ route('receipts.create') }}" wire:navigate><i class="fa fa-plus" aria-hidden="true"></i> Tilføj kvittering</a>
</x-layouts.app-shell>
