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
        <a class="reg-card" href="{{ route('kitchen.index') }}" wire:navigate style="--c: var(--koekken)">
            <h3>Køkken</h3>
            <p class="what">Opskrifter, indkøb og hvad der står på lager.</p>
            <div class="reg-links">
                <span class="chip">Opskrifter</span><span class="chip">Indkøbsliste</span><span class="chip">Lager</span>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->recipes->count }}</span> opskrifter</div>
        </a>

        <a class="reg-card" href="{{ route('nature.dashboard') }}" wire:navigate style="--c: var(--natur)">
            <h3>Natur</h3>
            <p class="what">Fuglearter, observationer og turene ud i det.</p>
            <div class="reg-links">
                <span class="chip">Observationer</span><span class="chip">Arter</span><span class="chip">Vilde planter</span>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->observations->count }}</span> observationer</div>
        </a>

        <a class="reg-card" href="{{ route('workshop.index') }}" wire:navigate style="--c: var(--vaerksted)">
            <h3>Værksted</h3>
            <p class="what">3D-print, filament og projekter der er i gang.</p>
            <div class="reg-links">
                <span class="chip">Printkø</span><span class="chip">Materialer</span><span class="chip">Kunder</span>
            </div>
            <div class="reg-note">Projekter & filament</div>
        </a>

        <a class="reg-card" href="{{ route('household.index') }}" wire:navigate style="--c: var(--husholdning)">
            <h3>Husholdning</h3>
            <p class="what">Kvitteringer, inventar og papirerne der skal gemmes.</p>
            <div class="reg-links">
                <span class="chip">Kvitteringer</span><span class="chip">Inventar</span><span class="chip">Kategorier</span>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->receipts->currentMonthCount }}</span> kvitteringer denne måned</div>
        </a>

        <a class="reg-card" href="{{ route('family.index') }}" wire:navigate style="--c: var(--familien)">
            <h3>Familien</h3>
            <p class="what">Profiler, post, adgange og indstillinger for siden.</p>
            <div class="reg-links">
                <span class="chip">Post</span><span class="chip">AI-adgang</span><span class="chip">Indstillinger</span>
            </div>
            <div class="reg-note"><span class="num">{{ $snapshot->inventory->count }}</span> genstande i inventar</div>
        </a>
    </div>
</x-layouts.app-shell>
