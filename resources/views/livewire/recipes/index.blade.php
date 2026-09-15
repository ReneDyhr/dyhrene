@section('title', $title)
<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <div class="view-head">
        <h1>{{ $title }}</h1>
        <p>{{ \count($recipes) }} opskrifter</p>
    </div>

    <a class="cta" href="{{ route('add') }}" wire:navigate style="margin-top: 0; margin-bottom: 24px;">
        <i class="fa fa-plus" aria-hidden="true"></i> Tilføj opskrift
    </a>

    @if (\count($recipes) > 0)
        <div class="list">
            @foreach ($recipes as $recipe)
                <x-recipe-row
                    :id="$recipe->id"
                    :name="$recipe->name"
                    :categories="$recipe->categories->pluck('name')->implode(', ')"
                    :ingredient-count="$recipe->ingredients->filter(fn ($i) => !\str_starts_with($i->name, '#'))->count()"
                    :tags="$recipe->tags->pluck('name')->all()"
                    :time-ago="$recipe->created_at?->locale('da')?->diffForHumans() ?? ''"
                />
            @endforeach
        </div>
    @else
        <p>Ingen opskrifter endnu.</p>
    @endif
</x-layouts.app-shell>
