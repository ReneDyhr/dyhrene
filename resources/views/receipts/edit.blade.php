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
                                            <td><span class="handle" style="cursor:move;"><i
                                                        class="fa fa-arrows-v"></i></span> <input type="text"
                                                    class="form-control form-control-sm"
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
                            <button type="button" wire:click="addItem" class="btn btn-success btn-sm"><i
                                    class="fa fa-plus"></i> Add Item</button>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    console.log('Test');
    function initReceiptSortable() {
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
    }
    initReceiptSortable();
    document.addEventListener('livewire:update', function () {
        initReceiptSortable();
    });
</script>
@endscript