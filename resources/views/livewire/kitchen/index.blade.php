<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <div class="view-head">
        <h1>Køkken</h1>
        <p>Opskrifter, indkøb og hvad der står på lager. Sæt ingredienser direkte på indkøbslisten fra en opskrift.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('recipes.index') }}" wire:navigate>Opskrifter</a>
        <a href="{{ route('shopping.list') }}" wire:navigate>Indkøbsliste</a>
        <a href="{{ route('storage') }}" wire:navigate>Lager</a>
        <a href="{{ route('settings.categories') }}" wire:navigate>Kategorier</a>
    </div>

    <div class="section-title">Senest tilføjet</div>
    @if (\count($snapshot->recentRecipes) > 0)
        <div class="list">
            @foreach ($snapshot->recentRecipes as $recipe)
                <x-recipe-row
                    :id="$recipe->id"
                    :name="$recipe->name"
                    :categories="\implode(', ', $recipe->categoryNames)"
                    :ingredient-count="$recipe->ingredientCount"
                    :tags="$recipe->tagNames"
                    :time-ago="\Carbon\Carbon::parse($recipe->createdAt)->locale('da')->diffForHumans()"
                />
            @endforeach
        </div>
    @else
        <p>Ingen opskrifter endnu.</p>
    @endif

    <div class="section-title">Favoritter</div>
    @if (\count($snapshot->favouriteRecipes) > 0)
        <div class="list">
            @foreach ($snapshot->favouriteRecipes as $recipe)
                <x-recipe-row
                    :id="$recipe->id"
                    :name="$recipe->name"
                    :categories="\implode(', ', $recipe->categoryNames)"
                    :ingredient-count="$recipe->ingredientCount"
                    :tags="$recipe->tagNames"
                    :time-ago="\Carbon\Carbon::parse($recipe->createdAt)->locale('da')->diffForHumans()"
                />
            @endforeach
        </div>
    @else
        <p>Ingen favoritter endnu.</p>
    @endif

    <a class="cta" href="{{ route('add') }}" wire:navigate><i class="fa fa-plus" aria-hidden="true"></i> Tilføj opskrift</a>
</x-layouts.app-shell>
