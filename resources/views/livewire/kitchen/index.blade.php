<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <section class="landing" aria-labelledby="kitchen-heading">
        <h1 id="kitchen-heading" class="landing__heading">Køkken</h1>

        <div class="overview-grid">
            <a class="overview-card" href="{{ route('recipes.index') }}">
                <span class="overview-card__kicker">Opskrifter</span>
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
        </div>

        <h2 class="landing__subheading">Genveje</h2>
        <ul class="landing-links">
            <li><a class="landing-links__link" href="{{ route('recipes.index') }}" wire:navigate>Opskrifter</a></li>
            <li><a class="landing-links__link" href="{{ route('shopping.list') }}" wire:navigate>Indkøbsliste</a></li>
            <li><a class="landing-links__link" href="{{ route('storage') }}" wire:navigate>Lager</a></li>
            <li><a class="landing-links__link" href="{{ route('settings.categories') }}" wire:navigate>Kategorier</a></li>
        </ul>
    </section>
</x-layouts.app-shell>
