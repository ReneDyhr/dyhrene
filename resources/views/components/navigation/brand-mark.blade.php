@props([
    'compact' => false,
    'homeHref',
])

<a class="{{ $compact ? 'mobile-brand' : 'brand' }}" href="{{ $homeHref }}" wire:navigate aria-label="Dyhrene forside">
    <svg class="brand-mark" width="{{ $compact ? 26 : 34 }}" height="{{ $compact ? 26 : 34 }}" viewBox="0 0 34 34" aria-hidden="true" focusable="false">
        <g fill="none" stroke-width="4.5" stroke-linecap="round" transform="translate(17 17)">
            <circle r="14" stroke="#E9704F" stroke-dasharray="15.6 72.4" transform="rotate(-90)" />
            <circle r="14" stroke="#63BC8B" stroke-dasharray="15.6 72.4" transform="rotate(-18)" />
            <circle r="14" stroke="#7BA2F0" stroke-dasharray="15.6 72.4" transform="rotate(54)" />
            <circle r="14" stroke="#DCA748" stroke-dasharray="15.6 72.4" transform="rotate(126)" />
            <circle r="14" stroke="#AE93DC" stroke-dasharray="15.6 72.4" transform="rotate(198)" />
        </g>
    </svg>

    @if ($compact)
        <span class="name">Dyhrene</span>
    @else
        <span class="brand-text">
            <span class="name">Dyhrene</span>
            <span class="sub">Familiens samlested</span>
        </span>
    @endif
</a>
