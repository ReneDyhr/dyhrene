<div>
    @section('title', 'Freezers')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <style>
                .freezer-list {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                    grid-auto-rows: 1fr;
                    gap: 0.5rem;
                    align-items: start;
                }
                .recipe {
                    display: flex;
                    flex-direction: column;
                    min-width: 0;
                }
                @media (max-width: 600px) {
                    .freezer-list {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            <div class="col-12">
                <div class="freezer-list">
                    @foreach($freezers as $freezer)
                        <div class="recipe" style="min-width:260px;max-width:350px;">
                            <h1>{{ $freezer->name }}</h1>
                            <ul id="freezer-items-{{ $freezer->id }}" class="shopping-list ui-sortable">
                                @foreach($freezer->items as $item)
                                    <li id="freezer-item-{{ $item->id }}" data-id="{{ $item->id }}">
                                        {{ $item->quantity }} {{ $item->name }}
                                        <span class="handle" style="cursor:move;"><i class="fa fa-arrows-v"></i></span>
                                        <span wire:confirm="Are you sure?" wire:click="removeItem({{ $item->id }})" class="close" style="cursor:pointer;">×</span>
                                    </li>
                                @endforeach
                            </ul>
                            <form wire:submit.prevent="addFreezerItem({{ $freezer->id }})" class="add-item mt-2">
                                <input type="hidden" wire:model.defer="freezerId" value="{{ $freezer->id }}" />
                                <input type="text" wire:model.defer="itemName.{{ $freezer->id }}" placeholder="Item name" class="border rounded px-2 py-1 w-24" />
                                <input type="number" wire:model.defer="itemQuantity.{{ $freezer->id }}" placeholder="Qty" min="1" class="border rounded px-2 py-1 w-16" />
                                <button type="submit" class="btn btn-success">Add</button>
                            </form>
                        </div>
                    @endforeach
                    <div class="recipe" style="min-width:260px;max-width:350px;">
                        <form wire:submit.prevent="addFreezer">
                            <input type="text" wire:model.defer="name" placeholder="Add new freezer/shelf..." class="border rounded px-2 py-1" />
                            <button type="submit" class="btn btn-default">Add Freezer</button>
                        </form>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="confirmRemoveModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this item?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="removeItemConfirmed">Delete</button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        console.log('HERE');
        @foreach($freezers as $freezer)
            console.log('Freezer ID: {{ $freezer->id }}');
            var el = document.getElementById('freezer-items-{{ $freezer->id }}');
            if (el) {
                console.log('Initializing sortable for freezer: {{ $freezer->id }}', el);
                if ($(el).data('ui-sortable')) {
                    $(el).sortable('destroy');
                }
                $(el).sortable({
                    axis: 'y',
                    handle: '.handle',
                    update: function(event, ui) {
                        let items = [];
                        $('#freezer-items-{{ $freezer->id }} li').each(function() {
                            items.push($(this).attr('data-id'));
                        });
                        @this.call('updateOrder', {{ $freezer->id }}, items);
                    }
                });
            } else {
                console.warn('Element not found for freezer: {{ $freezer->id }}');
            }
        @endforeach
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('livewire:load', function () {
                window.addEventListener('show-confirm-modal', event => {
                    $('#confirmRemoveModal').modal('show');
                });
                window.addEventListener('hide-confirm-modal', event => {
                    $('#confirmRemoveModal').modal('hide');
                });
            });
            document.addEventListener('livewire:update', function () {
                window.initSortable();
            });
        });
    </script>
    @endscript
</div>
