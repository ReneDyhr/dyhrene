@props(['area' => null])

@php
    $isHousehold = ($area?->value ?? '') === \App\Enums\AppArea::Household->value;
@endphp

<form class="search" action="{{ $isHousehold ? route('search.receipts') : route('search.recipes') }}" method="get" role="search" aria-label="{{ $isHousehold ? 'Søg i kvitteringer' : 'Søg i opskrifter' }}">
    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="#939C94" stroke-width="1.8" aria-hidden="true">
        <circle cx="7" cy="7" r="5"/><path d="M11 11l4 4"/>
    </svg>
    <input type="search" name="q" placeholder="{{ $isHousehold ? 'Søg i kvitteringer…' : 'Søg i opskrifter…' }}" aria-label="Søg på hele siden">
</form>
