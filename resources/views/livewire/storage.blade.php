<div>
    @section('title', 'Storage')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <style>
                .storage-list {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 0.5rem;
                    align-items: stretch;
                }
                .recipe {
                    display: flex;
                    flex-direction: column;
                    min-width: 0;
                    height: auto;
                }
                .storage-list > .recipe {
                    align-self: flex-start;
                }
                @media (max-width: 1000px) {
                    .storage-list {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            <div class="col-12">
                <div class="storage-list">
                    @foreach($storage as $storageUnit)
                        <div class="recipe">
                            <h1>{{ $storageUnit->name }}</h1>
                            <ul id="storage-items-{{ $storageUnit->id }}" class="shopping-list ui-sortable">
                                @foreach($storageUnit->items as $item)
                                    <li id="storage-item-{{ $item->id }}" data-id="{{ $item->id }}" wire:click="editItem({{ $item->id }})" style="cursor:pointer;">
                                        {{ $item->quantity }} {{ $item->name }}
                                        <span class="handle" style="cursor:move;" wire:click.stop><i class="fa fa-arrows-v"></i></span>
                                        <span wire:confirm="Are you sure?" wire:click.stop="removeItem({{ $item->id }})" class="close" style="cursor:pointer;">×</span>
                                    </li>
                                @endforeach
                            </ul>
                            <form wire:submit.prevent="addStorageItem({{ $storageUnit->id }})" class="add-item mt-3 px-2 py-2 bg-light rounded shadow-sm d-flex align-items-center gap-2 flex-row">
                                <input type="hidden" wire:model.defer="storageId" value="{{ $storageUnit->id }}" />
                                <input 
                                    type="number" 
                                    wire:model.defer="itemQuantity.{{ $storageUnit->id }}" 
                                    placeholder="Qty" 
                                    min="1" 
                                    class="form-control me-2"
                                    style="max-width: 20%; width: 100%; float: left; display: inline-block;"
                                />
                                <input 
                                    type="text" 
                                    wire:model.defer="itemName.{{ $storageUnit->id }}" 
                                    placeholder="Item name" 
                                    class="form-control me-2"
                                    style="max-width: 60%; float: left; display: inline-block;"
                                />
                                <button type="submit" class="btn btn-success" style="max-width: 20%; width: 100%; padding: 6px; float: left; display: inline-block;">
                                    <i class="fa fa-plus"></i> Add
                                </button>
                            </form>
                        </div>
                    @endforeach
                    <div class="recipe">
                        <form wire:submit.prevent="addStorage" class="add-item mt-3 px-2 py-2 bg-light rounded shadow-sm d-flex align-items-center gap-2 flex-row">
                            <input 
                                type="text" 
                                wire:model.defer="name" 
                                placeholder="Add new storage/shelf..." 
                                class="form-control me-2"
                                style="max-width: 80%; float: left; display: inline-block;"
                            />
                            <button 
                                type="submit" 
                                class="btn btn-success" 
                                style="max-width: 20%; width: 100%; padding: 6px; float: left; display: inline-block;"
                            >
                                <i class="fa fa-plus"></i> Add Storage
                            </button>
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

    <!-- Edit Item Modal -->
    <div wire:ignore.self class="modal fade" id="editItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form wire:submit.prevent="updateItem">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Item</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" wire:model.defer="editItemQuantity" class="form-control" min="1" />
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" wire:model.defer="editItemName" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @script
    <script>
        console.log('HERE');
        @foreach($storage as $storageUnit)
            console.log('Storage ID: {{ $storageUnit->id }}');
            var el = document.getElementById('storage-items-{{ $storageUnit->id }}');
            if (el) {
                console.log('Initializing sortable for storage: {{ $storageUnit->id }}', el);
                if ($(el).data('ui-sortable')) {
                    $(el).sortable('destroy');
                }
                $(el).sortable({
                    axis: 'y',
                    handle: '.handle',
                    update: function(event, ui) {
                        let items = [];
                        $('#storage-items-{{ $storageUnit->id }} li').each(function() {
                            items.push($(this).attr('data-id'));
                        });
                        @this.call('updateOrder', {{ $storageUnit->id }}, items);
                    }
                });
            } else {
                console.warn('Element not found for storage: {{ $storageUnit->id }}');
            }
        @endforeach

        // Register modal event listeners globally so Livewire can trigger them
        window.addEventListener('show-edit-modal', function () {
            $('#editItemModal').modal('show');
        });
        window.addEventListener('hide-edit-modal', function () {
            $('#editItemModal').modal('hide');
        });

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
