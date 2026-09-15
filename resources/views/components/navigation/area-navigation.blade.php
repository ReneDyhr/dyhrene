@props([
    'items',
    'mobile' => false,
])

@if ($mobile)
    <nav class="mobilenav" aria-label="Områder">
        @foreach ($items as $item)
            <a
                href="{{ $item['href'] }}"
                wire:navigate
                style="--c: var({{ $item['accentVar'] }})"
                @if ($item['isCurrent']) aria-current="true" @endif
            >
                <span class="bar" aria-hidden="true"></span>{{ $item['label'] }}
            </a>
        @endforeach
    </nav>
@else
    <nav class="tabs" aria-label="Områder">
        @foreach ($items as $item)
            <a
                class="tab"
                href="{{ $item['href'] }}"
                wire:navigate
                style="--c: var({{ $item['accentVar'] }})"
                @if ($item['isCurrent']) aria-current="true" @endif
            >
                <span class="tab-label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endif
