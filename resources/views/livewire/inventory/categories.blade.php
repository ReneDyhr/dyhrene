@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12">
                <div class="settings-categories">
                    <div class="panel-header">
                        <h1>Inventory Categories</h1>
                        <div class="actions">
                            <button href="#" class="btn btn-success" data-toggle="modal"
                                    data-target="#add-inventory-category">Add Category</button>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="col-12">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Color</th>
                                <th style="width:80px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>
                                    @if ($category->color)
                                        <span style="display:inline-block;width:20px;height:20px;background-color:{{ $category->color }};border:1px solid #ccc;border-radius:3px;"></span>
                                        {{ $category->color }}
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" wire:click="showEditCategory({{ $category->id }})">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" wire:click="showDeleteCategory({{ $category->id }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Add Modal -->
                    <div class="modal fade" id="add-inventory-category" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-sm" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title">Add Category</h4>
                                </div>
                                <form method="post" wire:submit.prevent="addCategory">
                                    <div class="modal-body">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" wire:model="addName" class="form-control noEnterSubmit">
                                            </div>
                                            <div class="form-group">
                                                <label>Color</label>
                                                <input type="color" wire:model="addColor" class="form-control">
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <input type="submit" class="noEnterSubmit btn btn-primary" value="Add">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal" id="edit-inventory-category" tabindex="-1">
                        <div class="modal-dialog modal-sm" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title">Edit Category</h4>
                                </div>
                                <form method="post" wire:submit.prevent="editCategory">
                                    <div class="modal-body">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" wire:model="editName" class="form-control noEnterSubmit">
                                            </div>
                                            <div class="form-group">
                                                <label>Color</label>
                                                <input type="color" wire:model="editColor" class="form-control">
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <input type="submit" name="edit" class="noEnterSubmit btn btn-primary" value="Edit">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal" @error('deleteCheck') style="display:block" @enderror id="delete-inventory-category" tabindex="-1">
                        <div class="modal-dialog modal-sm" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title">Delete Category</h4>
                                </div>
                                <form method="post" wire:submit.prevent="deleteCategory">
                                    <div class="modal-body">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="name" disabled wire:model="deleteName" class="form-control noEnterSubmit">
                                            </div>
                                            <label class="checkbox-inline">
                                                <input type="checkbox" wire:model="deleteCheck" name="check" value="1">
                                                Are you sure you want to delete this? This cannot be undone!
                                            </label>
                                            <div class="clear"></div>
                                            @error('deleteCheck') <span class="error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <input type="submit" name="delete" class="noEnterSubmit btn btn-danger" value="Delete">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="clear"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@script
<script>
    Livewire.on('showEditInventoryCategoryModal', (e) => {
        setTimeout(() => {
            $('#edit-inventory-category').modal('show');
        }, 500);
    });

    Livewire.on('showDeleteInventoryCategoryModal', (e) => {
        setTimeout(() => {
            $('#delete-inventory-category').modal('show');
        }, 500);
    });
</script>
@endscript
