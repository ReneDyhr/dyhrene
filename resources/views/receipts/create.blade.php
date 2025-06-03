<div>
    @section('title', 'Create Receipt')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Create Receipt</h1>
                        <form wire:submit.prevent="save">
                            @include('receipts.partials.form')
                            <button type="submit" class="btn btn-primary">Save</button>
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
                                <tbody>
                                    @foreach($itemEdits as $id => $item)
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm"
                                                    wire:model.defer="itemEdits.{{ $id }}.name"></td>
                                            <td><input type="number" class="form-control form-control-sm"
                                                    wire:model.defer="itemEdits.{{ $id }}.quantity"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm"
                                                    wire:model.defer="itemEdits.{{ $id }}.amount"></td>
                                            <td>
                                                <select class="form-control form-control-sm"
                                                    wire:model.defer="itemEdits.{{ $id }}.category_id">
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