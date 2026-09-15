@section('title', 'Lager')
<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <div class="view-head">
        <h1>Lager</h1>
        <p>Hvad der står på hylderne, og hvor meget der er tilbage.</p>
    </div>

    <form class="add-item" wire:submit.prevent="addStorage">
        <input type="text" wire:model="name" class="form-control" placeholder="Nyt lager / hylde…" aria-label="Nyt lager">
        <button type="submit" class="cta">Tilføj lager</button>
    </form>

    <div class="grid g2">
        @foreach ($storage as $storageUnit)
            <article class="card">
                <h3>{{ $storageUnit->name }}</h3>

                <ul id="storage-items-{{ $storageUnit->id }}" class="shopping-list">
                    @foreach ($storageUnit->items as $item)
                        <li id="storage-item-{{ $item->id }}" data-id="{{ $item->id }}" wire:click="editItem({{ $item->id }})">
                            <span class="name">
                                @if (\str_starts_with($item->name, '#'))
                                    <b>{{ \substr($item->name, 1) }}</b>
                                @else
                                    {{ $item->quantity }} {{ $item->name }}
                                @endif
                            </span>
                            <span class="handle" wire:click.stop title="Træk for at sortere"><i class="fa fa-arrows-v"></i></span>
                            <span wire:confirm="Er du sikker?" wire:click.stop="removeItem({{ $item->id }})" class="close" title="Slet">×</span>
                        </li>
                    @endforeach
                </ul>

                <form wire:submit.prevent="addStorageItem({{ $storageUnit->id }})" class="add-item" style="margin: 14px 0 0;">
                    <input type="number" wire:model.defer="itemQuantity.{{ $storageUnit->id }}" placeholder="Antal" min="1" class="form-control" style="max-width: 90px;" aria-label="Antal">
                    <input type="text" wire:model.defer="itemName.{{ $storageUnit->id }}" placeholder="Vare" class="form-control" aria-label="Vare">
                    <button type="submit" class="btn btn-success">Tilføj</button>
                </form>
            </article>
        @endforeach
    </div>

    <!-- Redigér vare -->
    <div wire:ignore.self class="modal fade" id="editItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form wire:submit.prevent="updateItem">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Redigér vare</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Luk">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Antal</label>
                            <input type="number" wire:model.defer="editItemQuantity" class="form-control" min="1">
                        </div>
                        <div class="form-group">
                            <label>Navn</label>
                            <input type="text" wire:model.defer="editItemName" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Annuller</button>
                        <button type="submit" class="btn btn-primary">Gem</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @script
    <script>
        function initStorageSortables() {
            document.querySelectorAll('ul[id^="storage-items-"]').forEach(function (el) {
                const $el = $(el);
                const storageId = parseInt(el.id.replace('storage-items-', ''), 10);

                if ($el.data('ui-sortable')) {
                    $el.sortable('destroy');
                }

                $el.sortable({
                    axis: 'y',
                    handle: '.handle',
                    update: function () {
                        const items = [];
                        $el.find('li').each(function () {
                            items.push($(this).attr('data-id'));
                        });
                        @this.call('updateOrder', storageId, items);
                    },
                });
            });
        }

        initStorageSortables();
        document.addEventListener('livewire:update', initStorageSortables);

        window.addEventListener('show-edit-modal', function () {
            $('#editItemModal').modal('show');
        });
        window.addEventListener('hide-edit-modal', function () {
            $('#editItemModal').modal('hide');
        });
    </script>
    @endscript
</x-layouts.app-shell>
