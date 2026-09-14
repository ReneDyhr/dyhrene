@props([
    'area' => null,
])

@php
    $navigation = \app(\App\Support\Navigation\AppNavigation::class);
    $currentArea = $area instanceof \App\Enums\AppArea
        ? $area
        : $navigation->areaForRoute(\Illuminate\Support\Facades\Route::currentRouteName());
    $items = $navigation->items($currentArea);
    $userName = \auth()->user()?->name ?? 'Gæst';
@endphp

<div {{ $attributes->class('app-shell') }} data-area="{{ $currentArea->value }}">
    <a class="app-shell__skip-link" href="#main-content">Spring til indhold</a>

    <aside class="app-shell__rail">
        <x-navigation.brand-mark :home-href="$navigation->homeHref()" />
        <x-navigation.area-navigation :items="$items" />

        <div class="app-shell__user" aria-label="Aktiv bruger">
            <span class="app-shell__user-mark" aria-hidden="true">{{ \mb_substr($userName, 0, 1) }}</span>
            <span class="app-shell__user-name">{{ $userName }}</span>
            <a href="{{ \route('logout') }}" class="app-shell__logout" title="Log ud" aria-label="Log ud">
                <i class="fa fa-sign-out" aria-hidden="true"></i>
            </a>
        </div>
    </aside>

    <div class="app-shell__content">
        <header class="app-shell__header">
            <x-navigation.brand-mark :home-href="$navigation->homeHref()" compact />
            <p class="app-shell__context" aria-label="Aktuelt område">{{ $currentArea->label() }}</p>
            <x-navigation.global-search />
        </header>

        <main id="main-content" class="app-shell__main" tabindex="-1">
            {{ $slot }}
        </main>
    </div>

    <x-navigation.area-navigation :items="$items" mobile />
</div>
