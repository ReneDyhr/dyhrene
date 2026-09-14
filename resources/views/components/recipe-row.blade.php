@props([
    'id',
    'name',
    'categories' => '',
    'ingredientCount' => 0,
    'tags' => [],
    'timeAgo' => '',
])

<div class="row">
    <span class="swatch" aria-hidden="true"></span>
    <div>
        <div class="lead">{{ $name }}</div>
        <div class="sub">
            @if ($categories !== '')
                {{ $categories }} ·
            @endif
            {{ $ingredientCount }} ingredienser
            @if ($timeAgo !== '')
                · {{ $timeAgo }}
            @endif
        </div>
    </div>

    @if (\count($tags) > 0)
        <div class="taglist">
            @foreach ($tags as $tag)
                <a href="/tag/{{ $tag }}" class="tag">{{ $tag }}</a>
            @endforeach
        </div>
    @endif

    <a class="right" href="{{ route('single', $id) }}" wire:navigate>Åbn →</a>
</div>
