@section('title', $title)
<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <section class="landing" aria-labelledby="recipe-heading">
        <article class="recipe-detail">
            <header class="recipe-detail__header">
                <h1 id="recipe-heading" class="recipe-detail__heading">{{ $this->recipe->name }}</h1>

                <div class="recipe-detail__actions">
                    <button wire:click="toggleFavourite" class="recipe-detail__action" title="Favorit" aria-label="Favorit">
                        <i class="fa fa-star @if ($this->recipe->favourite) favorite @endif"></i>
                    </button>
                    <a href="{{ $this->recipe->id }}/edit" class="recipe-detail__action" title="Redigér" aria-label="Redigér">
                        <i class="fa fa-edit"></i>
                    </a>
                    <button wire:click="delete" wire:confirm="Er du sikker på, at du vil slette denne opskrift? Dette kan ikke fortrydes!"
                        class="recipe-detail__action recipe-detail__action--danger" title="Slet" aria-label="Slet">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </header>

            <div class="recipe-detail__section">
                <h2>Ingredienser</h2>
                <ul class="recipe-detail__ingredients">
                    @foreach ($this->recipe->ingredients as $ingredient)
                        @if (\str_starts_with($ingredient->name, '#'))
                            <li class="recipe-detail__section-title">{{ \substr($ingredient->name, 1) }}</li>
                        @else
                            <li>{{ $ingredient->name }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>

            @if ($this->recipe->description)
                <div class="recipe-detail__section">
                    <h2>Fremgangsmåde</h2>
                    <div class="recipe-detail__text">{!! nl2br(e($this->recipe->description)) !!}</div>
                </div>
            @endif

            @if (!empty($this->recipe->note))
                <div class="recipe-detail__section">
                    <h2>Noter</h2>
                    <div class="recipe-detail__text">{!! nl2br(e($this->recipe->note)) !!}</div>
                </div>
            @endif

            @if ($this->recipe->tags->count() > 0 || $this->recipe->categories->count() > 0)
                <div class="recipe-detail__meta">
                    @foreach ($this->recipe->tags as $tag)
                        <a href="/tag/{{ $tag->name }}" class="chip">{{ $tag->name }}</a>
                    @endforeach
                    @foreach ($this->recipe->categories as $category)
                        <a href="/category/{{ $category->slug }}" class="chip">{{ $category->name }}</a>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
</x-layouts.app-shell>
