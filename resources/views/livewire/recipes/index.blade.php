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
        <div class="recipe-grid">
            @foreach ($recipes as $recipe)
                <article class="recipe-card">
                    <h2 class="recipe-card__title">
                        <a href="{{ route('single', $recipe->id) }}" wire:navigate>{{ $recipe->name }}</a>
                    </h2>

                    <ul class="recipe-card__ingredients">
                        @foreach ($recipe->ingredients as $ingredient)
                            @if (\str_starts_with($ingredient->name, '#'))
                                <li class="recipe-card__section">{{ \substr($ingredient->name, 1) }}</li>
                            @else
                                <li>{{ $ingredient->name }}</li>
                            @endif
                        @endforeach
                    </ul>

                    @if ($recipe->tags->count() > 0 || $recipe->categories->count() > 0)
                        <div class="recipe-card__tags">
                            @foreach ($recipe->tags as $tag)
                                <a href="/tag/{{ $tag->name }}" class="chip">{{ $tag->name }}</a>
                            @endforeach
                            @foreach ($recipe->categories as $category)
                                <a href="/category/{{ $category->slug }}" class="chip">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @else
        <p class="landing__empty">Ingen opskrifter endnu.</p>
    @endif
</x-layouts.app-shell>
