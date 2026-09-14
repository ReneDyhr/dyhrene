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
    $today = \Illuminate\Support\Carbon::now()->locale('da')->isoFormat('dddd D. MMMM');
@endphp

<div {{ $attributes->class('binder app-shell') }} data-area="{{ $currentArea->value }}">
    <a class="skip-link" href="#main-content">Spring til indhold</a>

    <aside class="rail">
        <x-navigation.brand-mark :home-href="$navigation->homeHref()" />
        <x-navigation.area-navigation :items="$items" />

        <div class="rail-foot">
            <span class="eyebrow">Logget ind</span>
            <div class="who">{{ $userName }}</div>
            <a href="{{ \route('logout') }}" class="rail-logout" title="Log ud">
                <i class="fa fa-sign-out" aria-hidden="true"></i> Log ud
            </a>
        </div>
    </aside>

    <div class="sheet">
        <header class="topbar">
            <x-navigation.brand-mark :home-href="$navigation->homeHref()" compact />
            <div class="crumb">
                <span class="h">{{ $currentArea->label() }}</span>
                <span class="date">{{ $today }}</span>
            </div>
            <x-navigation.global-search />
        </header>

        <main id="main-content" class="view" tabindex="-1">
            {{ $slot }}
        </main>
    </div>

    <x-navigation.area-navigation :items="$items" mobile />
</div>
