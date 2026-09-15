@section('title', $title)
<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <div class="view-head">
        <h1>Indkøbsliste</h1>
        <p>Sæt varer på listen og kryds dem af, mens du handler. Træk i håndtaget for at sortere.</p>
    </div>

    <form class="add-item" wire:submit.prevent="addItem">
        <input type="text" wire:model="item" class="form-control" placeholder="Tilføj vare…" aria-label="Tilføj vare">
        <button type="submit" class="cta">Tilføj</button>
    </form>

    <div class="section-title">På listen</div>
    <ul id="shopping-list" class="shopping-list">
        @foreach ($sortedItems as $item)
            <li id="shopping-list-row_{{ $item->id }}"
                data-id="{{ $item->id }}"
                class="@if ($item->isSectionHeader()) section-header @endif"
                @if (! $item->isSectionHeader()) wire:click="check({{ $item->id }})" @endif>
                @if ($item->isSectionHeader())
                    <span class="name"><b>{{ \substr($item->name, 1) }}</b></span>
                @else
                    <span class="check" aria-hidden="true"></span>
                    <span class="name">{{ $item->name }}</span>
                    <span class="handle" title="Træk for at sortere"><i class="fa fa-arrows-v"></i></span>
                @endif
                <span wire:confirm="Er du sikker?" wire:click.stop="delete({{ $item->id }})" class="close" title="Slet">×</span>
            </li>
        @endforeach
    </ul>

    @if ($sortedCheckedItems->count())
        <div class="section-title" style="margin-top: 24px;">
            Krydset af
            <button wire:click="clearChecked" class="btn btn-xs" style="float: right;">Tøm</button>
        </div>
        <ul id="shopping-checked" class="shopping-list">
            @foreach ($sortedCheckedItems as $item)
                <li class="checked" data-id="{{ $item->id }}"
                    @if (! $item->isSectionHeader()) wire:click="uncheck({{ $item->id }})" @endif>
                    @if ($item->isSectionHeader())
                        <span class="name"><b>{{ \substr($item->name, 1) }}</b></span>
                    @else
                        <span class="check" aria-hidden="true"></span>
                        <span class="name">{{ $item->name }}</span>
                    @endif
                    <span wire:confirm="Er du sikker?" wire:click.stop="delete({{ $item->id }})" class="close" title="Slet">×</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app-shell>
@script
<script>
    $('#shopping-list').sortable({
        axis: 'y',
        handle: '.handle',
        update: function () {
            const items = [];
            $('#shopping-list li').each(function () {
                items.push($(this).attr('data-id'));
            });
            @this.call('updateOrder', items);
        },
    });

    window.Echo.join('user.' + window.userId).listen('ShoppingList', (e) => {
        if (e.type === 'update') {
            @this.call('updateList');
        }
    });
</script>
@endscript
