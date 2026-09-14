<x-layouts.app-shell :area="\App\Enums\AppArea::Workshop">
    <section class="landing" aria-labelledby="workshop-heading">
        <h1 id="workshop-heading" class="landing__heading">Værksted</h1>

        <ul class="landing-links">
            <li><a class="landing-links__link" href="{{ route('print-jobs.index') }}" wire:navigate>Print jobs</a></li>
            <li><a class="landing-links__link" href="{{ route('print-materials.index') }}" wire:navigate>Materialer</a></li>
            <li><a class="landing-links__link" href="{{ route('print-material-types.index') }}" wire:navigate>Materialetyper</a></li>
            <li><a class="landing-links__link" href="{{ route('print-customers.index') }}" wire:navigate>Kunder</a></li>
            <li><a class="landing-links__link" href="{{ route('print-settings.edit') }}" wire:navigate>Indstillinger</a></li>
        </ul>
    </section>
</x-layouts.app-shell>
