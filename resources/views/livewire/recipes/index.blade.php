@section('title', $title)
<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <section class="landing" aria-labelledby="recipes-heading">
        <div class="landing__toolbar">
            <h1 id="recipes-heading" class="landing__heading">{{ $title }}</h1>
            <a href="{{ route('add') }}" class="landing__action"><i class="fa fa-plus" aria-hidden="true"></i> Tilføj opskrift</a>
        </div>

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
    </section>
</x-layouts.app-shell>
