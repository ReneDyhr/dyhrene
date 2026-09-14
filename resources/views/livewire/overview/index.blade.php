<x-layouts.app-shell :area="\App\Enums\AppArea::Overview">
    <section class="landing" aria-labelledby="overview-heading">
        <h1 id="overview-heading" class="landing__heading">Oversigt</h1>

        <div class="overview-grid">
            <a class="overview-card" href="{{ route('recipes.index') }}">
                <span class="overview-card__kicker">Køkken</span>
                <span class="overview-card__stat">{{ $snapshot->recipes->count }}</span>
                <span class="overview-card__label">opskrifter</span>
                @if ($snapshot->recipes->latest !== null)
                    <span class="overview-card__meta">Nyeste: {{ $snapshot->recipes->latest->name }}</span>
                @endif
            </a>

            <a class="overview-card" href="{{ route('shopping.list') }}">
                <span class="overview-card__kicker">Indkøb</span>
                <span class="overview-card__stat">{{ $snapshot->shoppingList->activeActionableCount }}</span>
                <span class="overview-card__label">aktive varer</span>
            </a>

            <a class="overview-card" href="{{ route('receipts.index') }}">
                <span class="overview-card__kicker">Husholdning</span>
                <span class="overview-card__stat">{{ $snapshot->receipts->currentMonthCount }}</span>
                <span class="overview-card__label">kvitteringer denne måned</span>
                @if ($snapshot->receipts->latest !== null)
                    <span class="overview-card__meta">Nyeste: {{ $snapshot->receipts->latest->name }}</span>
                @endif
            </a>

            <a class="overview-card" href="{{ route('inventory.index') }}">
                <span class="overview-card__kicker">Inventar</span>
                <span class="overview-card__stat">{{ $snapshot->inventory->count }}</span>
                <span class="overview-card__label">genstande</span>
            </a>

            <a class="overview-card" href="{{ route('nature.dashboard') }}">
                <span class="overview-card__kicker">Natur</span>
                <span class="overview-card__stat">{{ $snapshot->observations->count }}</span>
                <span class="overview-card__label">observationer</span>
            </a>

            <a class="overview-card" href="{{ route('wild-edibles.index') }}">
                <span class="overview-card__kicker">Vilde planter</span>
                <span class="overview-card__stat">{{ $snapshot->wildEdibles->count }}</span>
                <span class="overview-card__label">spiselige</span>
            </a>
        </div>
    </section>
</x-layouts.app-shell>
