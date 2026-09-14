@props([
    'items',
    'mobile' => false,
])

@php
    $label = $mobile ? 'Mobil navigation' : 'Primær navigation';
    $mode = $mobile ? 'app-area-navigation--mobile' : 'app-area-navigation--desktop';
@endphp

<nav class="app-area-navigation {{ $mode }}" aria-label="{{ $label }}">
    <ul class="app-area-navigation__list">
        @foreach ($items as $item)
            <li class="app-area-navigation__item">
                <a
                    class="app-area-navigation__link {{ $item['isCurrent'] ? 'is-current' : '' }}"
                    href="{{ $item['href'] }}"
                    wire:navigate
                    data-area="{{ $item['area']->value }}"
                    @if ($item['isCurrent']) aria-current="page" @endif
                >
                    <span class="app-area-navigation__icon" aria-hidden="true">{{ $item['icon'] }}</span>
                    <span class="app-area-navigation__label">{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
