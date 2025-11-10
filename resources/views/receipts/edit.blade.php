<div>
    @section('title', 'Edit Receipt')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Edit Receipt</h1>
                        <form wire:submit.prevent="save">
                            @include('receipts.partials.form')
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                        <div class="mt-4">
                            <h2>Items</h2>
                            
                            <!-- Desktop Table View -->
                            <div class="receipt-items-desktop">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Quantity</th>
                                            <th>Amount</th>
                                            <th>Category</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="receipt-items-list">
                                        @foreach($itemEdits as $id => $item)
                                            <tr data-id="{{ $id }}">
                                                <td><input type="text" class="form-control form-control-sm"
                                                        wire:model="itemEdits.{{ $id }}.name" wire:change="calculateTotal"></td>
                                                <td><input type="number" class="form-control form-control-sm"
                                                        wire:model="itemEdits.{{ $id }}.quantity" wire:change="calculateTotal">
                                                </td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm"
                                                        wire:model="itemEdits.{{ $id }}.amount" wire:change="calculateTotal">
                                                </td>
                                                <td>
                                                    <select class="form-control form-control-sm"
                                                        wire:model="itemEdits.{{ $id }}.category_id"
                                                        wire:change="calculateTotal">
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <span class="handle btn btn-default btn-sm" style="cursor:move;"><i
                                                            class="fa fa-arrows-v"></i></span>
                                                    <button type="button" wire:click="deleteItem('{{ $id }}')"
                                                        class="btn btn-danger btn-sm">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td class="fw-bold text-end">
                                                Total:
                                                {{ collect($itemEdits)->reduce(fn($carry, $item) => $carry + ($item['amount'] * $item['quantity']), 0) }}
                                                {{ $data['currency'] ?? '' }}
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Mobile Card View -->
                            <div class="receipt-items-mobile">
                                <div id="receipt-items-list-mobile">
                                    @foreach($itemEdits as $id => $item)
                                        <div class="receipt-item-card" data-id="{{ $id }}">
                                            <div class="receipt-item-field">
                                                <label>Name</label>
                                                <input type="text" class="form-control"
                                                    wire:model="itemEdits.{{ $id }}.name" wire:change="calculateTotal">
                                            </div>
                                            <div class="receipt-item-row">
                                                <div class="receipt-item-field">
                                                    <label>Quantity</label>
                                                    <input type="number" class="form-control"
                                                        wire:model="itemEdits.{{ $id }}.quantity" wire:change="calculateTotal">
                                                </div>
                                                <div class="receipt-item-field">
                                                    <label>Amount</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        wire:model="itemEdits.{{ $id }}.amount" wire:change="calculateTotal">
                                                </div>
                                            </div>
                                            <div class="receipt-item-field">
                                                <label>Category</label>
                                                <select class="form-control"
                                                    wire:model="itemEdits.{{ $id }}.category_id"
                                                    wire:change="calculateTotal">
                                                    @foreach($categories as $cat)
                                                        <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="receipt-item-actions">
                                                <span class="handle btn btn-default btn-sm" style="cursor:move;"><i
                                                        class="fa fa-arrows-v"></i></span>
                                                <button type="button" wire:click="deleteItem('{{ $id }}')"
                                                    class="btn btn-danger btn-sm">Delete</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="receipt-total-mobile">
                                    <strong>Total: {{ collect($itemEdits)->reduce(fn($carry, $item) => $carry + ($item['amount'] * $item['quantity']), 0) }} {{ $data['currency'] ?? '' }}</strong>
                                </div>
                            </div>

                            <button type="button" wire:click="addItem" class="btn btn-success btn-sm"><i
                                    class="fa fa-plus"></i> Add Item</button>
                        </div>

                        <style>
                            /* Hide mobile view on desktop */
                            .receipt-items-mobile {
                                display: none;
                            }

                            /* Show desktop table on desktop */
                            .receipt-items-desktop {
                                display: block;
                            }

                            /* Mobile styles */
                            @media screen and (max-width: 768px) {
                                .receipt-items-desktop {
                                    display: none;
                                }

                                .receipt-items-mobile {
                                    display: block;
                                }

                                .receipt-item-card {
                                    background: #fff;
                                    border: 1px solid #ddd;
                                    border-radius: 4px;
                                    padding: 15px;
                                    margin-bottom: 15px;
                                }

                                .receipt-item-field {
                                    margin-bottom: 15px;
                                }

                                .receipt-item-field label {
                                    display: block;
                                    font-weight: bold;
                                    margin-bottom: 5px;
                                    font-size: 0.9rem;
                                    color: #333;
                                }

                                .receipt-item-field input,
                                .receipt-item-field select {
                                    width: 100%;
                                    padding: 8px;
                                    border: 1px solid #ddd;
                                    border-radius: 4px;
                                    font-size: 1rem;
                                }

                                .receipt-item-row {
                                    display: flex;
                                    gap: 10px;
                                }

                                .receipt-item-row .receipt-item-field {
                                    flex: 1;
                                    margin-bottom: 15px;
                                }

                                .receipt-item-actions {
                                    display: flex;
                                    gap: 10px;
                                    align-items: center;
                                    margin-top: 10px;
                                }

                                .receipt-item-actions .btn {
                                    flex: 1;
                                }

                                .receipt-total-mobile {
                                    text-align: center;
                                    padding: 15px;
                                    background: #f9f9f9;
                                    border: 1px solid #ddd;
                                    border-radius: 4px;
                                    margin-top: 15px;
                                    font-size: 1.1rem;
                                }
                            }
                        </style>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    function initReceiptSortable() {
        // Desktop table sortable
        var el = document.getElementById('receipt-items-list');
        if (el && window.$ && $.fn.sortable) {
            if ($(el).data('ui-sortable')) {
                $(el).sortable('destroy');
            }
            $(el).sortable({
                axis: 'y',
                handle: '.handle',
                items: '> tr',
                update: function (event, ui) {
                    let ids = [];
                    $('#receipt-items-list tr').each(function () {
                        ids.push($(this).attr('data-id'));
                    });
                    @this.call('updateItemOrder', ids);
                }
            });
        }

        // Mobile card sortable
        var mobileEl = document.getElementById('receipt-items-list-mobile');
        if (mobileEl && window.$ && $.fn.sortable) {
            if ($(mobileEl).data('ui-sortable')) {
                $(mobileEl).sortable('destroy');
            }
            $(mobileEl).sortable({
                axis: 'y',
                handle: '.handle',
                items: '> .receipt-item-card',
                update: function (event, ui) {
                    let ids = [];
                    $('#receipt-items-list-mobile .receipt-item-card').each(function () {
                        ids.push($(this).attr('data-id'));
                    });
                    @this.call('updateItemOrder', ids);
                }
            });
        }
    }
    initReceiptSortable();
    document.addEventListener('livewire:update', function () {
        initReceiptSortable();
    });
</script>
@endscript