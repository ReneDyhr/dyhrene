<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <section class="landing" aria-labelledby="household-heading">
        <h1 id="household-heading" class="landing__heading">Husholdning</h1>

        <ul class="landing-links">
            <li><a class="landing-links__link" href="{{ route('receipts.index') }}" wire:navigate>Kvitteringer</a></li>
            <li><a class="landing-links__link" href="{{ route('inventory.index') }}" wire:navigate>Inventar</a></li>
            <li><a class="landing-links__link" href="{{ route('inventory.categories') }}" wire:navigate>Inventarkategorier</a></li>
        </ul>
    </section>
</x-layouts.app-shell>
