<x-layouts.app-shell :area="\App\Enums\AppArea::Overview">
    @php
        $hour = (int) \now('Europe/Copenhagen')->format('G');
        $greeting = match (true) {
            $hour < 5 => 'Godnat',
            $hour < 11 => 'Godmorgen',
            $hour < 17 => 'Goddag',
            default => 'God aften',
        };
    @endphp

    <div class="view-head">
        <h1>{{ $greeting }}, {{ \auth()->user()?->name ?? 'René' }}</h1>
        <p>Alt hvad vi samler på, ét sted. Vælg et område — hver farve er sit eget hjørne af huset.</p>
    </div>

    <div class="today">
        <div class="cell" style="--k: var(--koekken)">
            <span class="eyebrow">Ny opskrift</span>
            <b>{{ $snapshot->recipes->latest?->name ?? 'Ingen opskrifter endnu' }}</b>
            <span>{{ $snapshot->recipes->count }} opskrifter i alt</span>
        </div>
        <div class="cell" style="--k: var(--husholdning)">
            <span class="eyebrow">Indkøb</span>
            <b>{{ $snapshot->shoppingList->activeActionableCount }} varer på listen</b>
            <span>Aktive indkøb lige nu</span>
        </div>
        <div class="cell" style="--k: var(--natur)">
            <span class="eyebrow">Seneste observation</span>
            <b>{{ $snapshot->observations->count }} observationer</b>
            <span>{{ $snapshot->wildEdibles->count }} vilde planter registreret</span>
        </div>
    </div>

    <div class="register">
        <div class="reg-card reg-card--link" style="--c: var(--koekken)">
            <a class="stretched-link" href="{{ route('kitchen.index') }}" wire:navigate aria-label="Køkken"></a>
            <h3>Køkken</h3>
            <p class="what">Opskrifter, indkøb og hvad der står på lager.</p>
            <div class="reg-links">
                <a class="chip" href="{{ route('recipes.index') }}" wire:navigate>Opskrifter</a>
                <a class="chip" href="{{ route('shopping.list') }}" wire:navigate>Indkøbsliste</a>
                <a class="chip" href="{{ route('storage') }}" wire:navigate>Lager</a>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->recipes->count }}</span> opskrifter</div>
        </div>

        <div class="reg-card reg-card--link" style="--c: var(--natur)">
            <a class="stretched-link" href="{{ route('nature.dashboard') }}" wire:navigate aria-label="Natur"></a>
            <h3>Natur</h3>
            <p class="what">Fuglearter, observationer og turene ud i det.</p>
            <div class="reg-links">
                <a class="chip" href="{{ route('observations.index') }}" wire:navigate>Observationer</a>
                <a class="chip" href="{{ route('species.index') }}" wire:navigate>Arter</a>
                <a class="chip" href="{{ route('wild-edibles.index') }}" wire:navigate>Vilde planter</a>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->observations->count }}</span> observationer</div>
        </div>

        <div class="reg-card reg-card--link" style="--c: var(--vaerksted)">
            <a class="stretched-link" href="{{ route('workshop.index') }}" wire:navigate aria-label="Værksted"></a>
            <h3>Værksted</h3>
            <p class="what">3D-print, filament og projekter der er i gang.</p>
            <div class="reg-links">
                <a class="chip" href="{{ route('print-jobs.index') }}" wire:navigate>Printkø</a>
                <a class="chip" href="{{ route('print-materials.index') }}" wire:navigate>Materialer</a>
                <a class="chip" href="{{ route('print-customers.index') }}" wire:navigate>Kunder</a>
            </div>
            <div class="reg-note">Projekter & filament</div>
        </div>

        <div class="reg-card reg-card--link" style="--c: var(--husholdning)">
            <a class="stretched-link" href="{{ route('household.index') }}" wire:navigate aria-label="Hus"></a>
            <h3>Hus</h3>
            <p class="what">Kvitteringer, inventar og papirerne der skal gemmes.</p>
            <div class="reg-links">
                <a class="chip" href="{{ route('receipts.index') }}" wire:navigate>Kvitteringer</a>
                <a class="chip" href="{{ route('inventory.index') }}" wire:navigate>Inventar</a>
                <a class="chip" href="{{ route('inventory.categories') }}" wire:navigate>Kategorier</a>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->receipts->currentMonthCount }}</span> kvitteringer denne måned</div>
        </div>

        <div class="reg-card reg-card--link" style="--c: var(--familien)">
            <a class="stretched-link" href="{{ route('family.index') }}" wire:navigate aria-label="Familien"></a>
            <h3>Familien</h3>
            <p class="what">Profiler, post, adgange og indstillinger for siden.</p>
            <div class="reg-links">
                <a class="chip" href="{{ route('mail.inbox') }}" wire:navigate>Post</a>
                <a class="chip" href="{{ route('settings.mcp') }}" wire:navigate>AI-adgang</a>
                <a class="chip" href="{{ route('settings.mcp') }}" wire:navigate>Indstillinger</a>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->inventory->count }}</span> genstande i inventar</div>
        </div>
    </div>
</x-layouts.app-shell>
