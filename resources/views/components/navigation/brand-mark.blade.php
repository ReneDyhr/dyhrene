@props([
    'compact' => false,
    'homeHref',
])

<a class="app-brand {{ $compact ? 'app-brand--compact' : '' }}" href="{{ $homeHref }}" wire:navigate aria-label="Dyhrene forside">
    <svg class="app-brand__mark" width="34" height="34" viewBox="0 0 34 34" aria-hidden="true" focusable="false">
        <g fill="none" stroke-width="4" stroke-linecap="round" transform="translate(17 17)">
            <circle r="14" stroke="var(--app-area-kitchen)" stroke-dasharray="15.6 72.4" transform="rotate(-90)" />
            <circle r="14" stroke="var(--app-area-nature)" stroke-dasharray="15.6 72.4" transform="rotate(-18)" />
            <circle r="14" stroke="var(--app-area-workshop)" stroke-dasharray="15.6 72.4" transform="rotate(54)" />
            <circle r="14" stroke="var(--app-area-household)" stroke-dasharray="15.6 72.4" transform="rotate(126)" />
            <circle r="14" stroke="var(--app-area-family)" stroke-dasharray="15.6 72.4" transform="rotate(198)" />
        </g>
    </svg>
    <span class="app-brand__text">
        <span class="app-brand__name">Dyhrene</span>
        @if (! $compact)
            <span class="app-brand__subtitle">Familiens samlested</span>
        @endif
    </span>
</a>
