<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <section class="landing" aria-labelledby="household-heading">
        <h1 id="household-heading" class="landing__heading">Husholdning</h1>

        <div class="overview-grid">
            <a class="overview-card" href="{{ route('receipts.index') }}">
                <span class="overview-card__kicker">Kvitteringer</span>
                <span class="overview-card__stat">{{ $snapshot->receipts->currentMonthCount }}</span>
                <span class="overview-card__label">denne måned</span>
                @if ($snapshot->receipts->latest !== null)
                    <span class="overview-card__meta">Nyeste: {{ $snapshot->receipts->latest->name }}</span>
                @endif
            </a>

            <a class="overview-card" href="{{ route('inventory.index') }}">
                <span class="overview-card__kicker">Inventar</span>
                <span class="overview-card__stat">{{ $snapshot->inventory->count }}</span>
                <span class="overview-card__label">genstande</span>
            </a>
        </div>

        <h2 class="landing__subheading">Genveje</h2>
        <ul class="landing-links">
            <li><a class="landing-links__link" href="{{ route('receipts.index') }}" wire:navigate>Kvitteringer</a></li>
            <li><a class="landing-links__link" href="{{ route('inventory.index') }}" wire:navigate>Inventar</a></li>
            <li><a class="landing-links__link" href="{{ route('inventory.categories') }}" wire:navigate>Inventarkategorier</a></li>
        </ul>
    </section>
</x-layouts.app-shell>
