<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <section class="landing" aria-labelledby="kitchen-heading">
        <h1 id="kitchen-heading" class="landing__heading">Køkken</h1>

        <ul class="landing-links">
            <li><a class="landing-links__link" href="{{ route('recipes.index') }}" wire:navigate>Opskrifter</a></li>
            <li><a class="landing-links__link" href="{{ route('shopping.list') }}" wire:navigate>Indkøbsliste</a></li>
            <li><a class="landing-links__link" href="{{ route('storage') }}" wire:navigate>Lager</a></li>
            <li><a class="landing-links__link" href="{{ route('settings.categories') }}" wire:navigate>Kategorier</a></li>
        </ul>
    </section>
</x-layouts.app-shell>
