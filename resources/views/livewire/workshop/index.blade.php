<x-layouts.app-shell :area="\App\Enums\AppArea::Workshop">
    <div class="view-head">
        <h1>Værksted</h1>
        <p>3D-print og projekter. Her ligger også filamentbeholdningen, så du kan se hvad der er tilbage.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('print-jobs.index') }}" wire:navigate aria-current="page">Print jobs</a>
        <a href="{{ route('print-materials.index') }}" wire:navigate>Materialer</a>
        <a href="{{ route('print-material-types.index') }}" wire:navigate>Materialetyper</a>
        <a href="{{ route('print-customers.index') }}" wire:navigate>Kunder</a>
        <a href="{{ route('print-settings.edit') }}" wire:navigate>Indstillinger</a>
    </div>

    <div class="stats">
        <div class="stat"><div class="n">{{ $snapshot->draftCount }}</div><div class="l">Kladder</div></div>
        <div class="stat"><div class="n">{{ $snapshot->lockedCount }}</div><div class="l">Låste print jobs</div></div>
    </div>

    <a class="cta" href="{{ route('print-jobs.create') }}" wire:navigate><i class="fa fa-plus" aria-hidden="true"></i> Nyt print job</a>
</x-layouts.app-shell>
