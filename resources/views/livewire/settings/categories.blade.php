@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12">
                <div class="settings-categories">
                    <div class="panel-header">
                        <h1>Categories</h1>
                        <div class="actions">
                            <button href="#" class="btn btn-success" data-toggle="modal"
                                    data-target="#add">Add</button>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="col-12">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Icon</th>
                                <th style="width:80px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($categories as $category)
                            <tr>
                                <td>{{$category->name}}</td>
                                <td>
                                    <i class="icon icon-{{$category->icon->class}}"></i>
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
                    <div class="modal" id="edit" tabindex="-1">
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
                                        <div class="new-category">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Name</label>
                                                    <input type="text" wire:model="editName"
                                                           class="form-control noEnterSubmit">
                                                </div>
                                                <div class="form-group" wire:ignore>
                                                    <label>Icon
                                                        <i class="fa fa-info-circle"
                                                           title="Help>" data-toggle="popover"
                                                           data-placement="top"
                                                           data-content="Choose whatever icon you feel like fits the category, from the list of possible icons.<br>The icon chosen will be shown under the list of categories, as well as in the topbar/category-bar. This icon can be changed at any time."></i>
                                                    </label>
                                                    <select wire:model="editIcon" class="selectpicker form-control noEnterSubmit">
                                                        @foreach ($icons as $icon)
                                                            <option
                                                                    data-icon="icon-{{$icon->class}}"
                                                                    value="{{$icon->id}}">{{$icon->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="clear"></div>
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default"
                                                data-dismiss="modal">Close</button>
                                        <input type="submit" name="edit" class="noEnterSubmit btn btn-primary"
                                               value="Edit">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal" @error('deleteCheck') style="display:block" @enderror id="delete" tabindex="-1">
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
                                        <div class="new-category">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Name</label>
                                                    <input type="text" name="name" disabled
                                                           wire:model="deleteName"
                                                           class="form-control noEnterSubmit">
                                                </div>
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" wire:model="deleteCheck" name="check"
                                                           value="1">Are you sure to delete this? This cannot be undone!
                                                </label>
                                                <div class="clear"></div>
                                                @error('deleteCheck') <span class="error">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default"
                                                data-dismiss="modal">Close</button>
                                        <input type="submit" name="delete" class="noEnterSubmit btn btn-danger"
                                               value="Delete">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="add" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-sm" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title">Add Category</h4>
                                </div>
                                <form method="post" id="form" wire:submit.prevent="addCategory">
                                    <div class="modal-body">
                                        <div class="new-category">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Name</label>
                                                    <input type="text" wire:model="addName" class="form-control noEnterSubmit">
                                                </div>
                                                <div class="form-group">
                                                    <label>Icon
                                                        <i class="fa fa-info-circle" title="Help>"
                                                           data-toggle="popover" data-placement="top"
                                                           data-content="Choose whatever icon you feel like fits the category, from the list of possible icons.<br>The icon chosen will be shown under the list of categories, as well as in the topbar/category-bar. This icon can be changed at any time."></i>
                                                    </label>
                                                    <select wire:model="addIcon" class="selectpicker form-control noEnterSubmit">
                                                        @foreach ($icons as $icon)
                                                            <option data-icon="icon-{{$icon->class}}"
                                                                    value="{{$icon->id}}">{{$icon->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="clear"></div>
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default"
                                                data-dismiss="modal">Close</button>
                                        <input type="submit" class="noEnterSubmit btn btn-primary"
                                               value="Add">
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
    Livewire.on('showEditCategoryModal', (e) => {
        setTimeout(() => {
            $('#edit').modal('show');
            $('#edit .selectpicker').val(e[0].icon);
            $('#edit .selectpicker').selectpicker('refresh');
        }, 500);
    });

    Livewire.on('showDeleteCategoryModal', (e) => {
        setTimeout(() => {
            $('#delete').modal('show');
        }, 500);
    });
</script>
@endscript