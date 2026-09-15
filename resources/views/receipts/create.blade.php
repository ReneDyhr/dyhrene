@section('title', 'Ny kvittering')
<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <div class="view-head">
        <h1>Ny kvittering</h1>
        <p>Opret en kvittering og tilføj varer.</p>
    </div>

    <form wire:submit.prevent="save" class="card">
        @include('receipts.partials.form')

        <div class="section-title" style="margin: 24px 0 12px;">Varer</div>

        <div id="receipt-items-list">
            @forelse ($itemEdits ?? [] as $id => $item)
                <div class="receipt-item-card" data-id="{{ $id }}">
                    <div class="form-group">
                        <label>Vare</label>
                        <input type="text" class="form-control" wire:model="itemEdits.{{ $id }}.name" wire:change="calculateTotal">
                    </div>
                    <div class="receipt-item-row">
                        <div class="form-group">
                            <label>Antal</label>
                            <input type="number" class="form-control" wire:model="itemEdits.{{ $id }}.quantity" wire:change="calculateTotal">
                        </div>
                        <div class="form-group">
                            <label>Pris</label>
                            <input type="number" step="0.01" class="form-control" wire:model="itemEdits.{{ $id }}.amount" wire:change="calculateTotal">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select class="form-control" wire:model="itemEdits.{{ $id }}.category_id" wire:change="calculateTotal">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="receipt-item-actions">
                        <span class="handle" title="Træk for at sortere"><i class="fa fa-arrows-v"></i></span>
                        <button type="button" wire:click="deleteItem('{{ $id }}')" class="btn btn-danger btn-sm">Slet</button>
                    </div>
                </div>
            @empty
                <p>Ingen varer endnu.</p>
            @endforelse
        </div>

        <button type="button" wire:click="addItem" class="btn btn-success" style="margin-top: 4px;"><i class="fa fa-plus"></i> Tilføj vare</button>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Gem</button>
            <a href="{{ route('receipts.index') }}" class="btn btn-default" wire:navigate>Tilbage</a>
        </div>
    </form>
</x-layouts.app-shell>

@script
<script>
    (function () {
        function initReceiptSortable() {
            const el = document.getElementById('receipt-items-list');
            if (el && window.$ && $.fn.sortable) {
                if ($(el).data('ui-sortable')) {
                    $(el).sortable('destroy');
                }
                $(el).sortable({
                    axis: 'y',
                    handle: '.handle',
                    items: '> .receipt-item-card',
                    update: function () {
                        const ids = [];
                        $('#receipt-items-list .receipt-item-card').each(function () {
                            ids.push($(this).attr('data-id'));
                        });
                        @this.call('updateItemOrder', ids);
                    },
                });
            }
        }

        $(function () {
            initReceiptSortable();
        });
        Livewire.hook('morphed', () => initReceiptSortable());
    })();
</script>
@endscript
