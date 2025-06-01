@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12 recipe" style="position:relative;">
                <h1>Shopping List</h1>
            </div>
            <div class="col-12 recipe">
                <div class="add-item">
                    <input type="text" wire:model="item" wire:keydown.enter="addItem" id="add-item-name" placeholder="Title...">
                    <span wire:click="addItem" class="btn btn-success">Add</span>
                    <div style="clear:both"></div>
                </div>
                <div style="clear:both"></div>
                <div id="check-list">
                    <ul id="shopping-list" class="shopping-list ui-sortable">
                        @foreach ($sortedItems as $item)
                            <li id="shopping-list-row_{{$item->id}}" wire:click="check({{ $item->id }})" data-id="{{$item->id}}">{{$item->name}}<span class="handle"><i class="fa fa-arrows-v"></i></span><span wire:confirm="Are you sure?" wire:click="delete({{ $item->id }})" class="close">×</span></li>
                        @endforeach
                    </ul>
                    @if($sortedCheckedItems->count())
                        <div class="checked">
                        <h2>Checked <button id="empty-checked" wire:click="clearChecked" class="btn btn-default btn-xs">Empty checked</button>
                        </h2>
                            <ul id="shopping-checked" class="shopping-list">
                                @foreach ($sortedCheckedItems as $item)
                                    <li class="checked" wire:click="uncheck({{ $item->id }})" data-id="{{$item->id}}">{{$item->name}}<span wire:confirm="Are you sure?" wire:click="delete({{ $item->id }})" class="close">×</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@script
<script>
    $( "#shopping-list" ).sortable({
		axis: 'y',
		handle: ".handle",
		update: function(event, ui) {
            let items = [];
            $('#shopping-list li').each(function() {
                items.push($(this).attr('data-id'));
            });

            @this.call('updateOrder', items);
        }
	});
    window.Echo.join('user.' + window.userId).listen('ShoppingList', (e) => {
        if (e.type == 'update') {
            @this.call('updateList');
        }
    });
</script>
@endscript