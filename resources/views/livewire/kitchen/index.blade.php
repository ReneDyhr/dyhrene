<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <div class="view-head">
        <h1>Køkken</h1>
        <p>Opskrifter, indkøb og hvad der står på lager. Sæt ingredienser direkte på indkøbslisten fra en opskrift.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('recipes.index') }}" wire:navigate aria-current="page">Opskrifter</a>
        <a href="{{ route('shopping.list') }}" wire:navigate>Indkøbsliste</a>
        <a href="{{ route('storage') }}" wire:navigate>Lager</a>
        <a href="{{ route('settings.categories') }}" wire:navigate>Kategorier</a>
    </div>

    <div class="stats">
        <div class="stat"><div class="n">{{ $snapshot->recipes->count }}</div><div class="l">Opskrifter</div></div>
        <div class="stat"><div class="n">{{ $snapshot->shoppingList->activeActionableCount }}</div><div class="l">Aktive indkøb</div></div>
    </div>

    @if ($snapshot->recipes->latest !== null)
        <div class="section-title">Nyeste opskrift</div>
        <div class="list">
            <div class="row">
                <span class="swatch" style="--theme: var(--koekken)"></span>
                <div>
                    <div class="lead">{{ $snapshot->recipes->latest->name }}</div>
                    <div class="sub">Senest tilføjet</div>
                </div>
                <a class="right" href="{{ route('single', $snapshot->recipes->latest->id) }}">Åbn →</a>
            </div>
        </div>
    @endif

    <a class="cta" href="{{ route('add') }}" wire:navigate><i class="fa fa-plus" aria-hidden="true"></i> Tilføj opskrift</a>
</x-layouts.app-shell>
