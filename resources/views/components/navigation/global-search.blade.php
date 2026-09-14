<form class="app-recipe-search" action="{{ route('search') }}" method="get" role="search" aria-label="Opskriftssøgning">
    <label class="app-shell__visually-hidden" for="app-recipe-search">Søg i opskrifter</label>
    <input
        id="app-recipe-search"
        class="app-recipe-search__input"
        type="search"
        name="q"
        placeholder="Søg i opskrifter"
        aria-label="Søg i opskrifter"
    >
    <button class="app-recipe-search__submit" type="submit">
        <span aria-hidden="true">⌕</span>
        <span class="app-shell__visually-hidden">Søg i opskrifter</span>
    </button>
</form>
