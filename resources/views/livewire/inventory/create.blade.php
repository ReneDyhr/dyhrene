<div>
    @section('title', $title)
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Create Item</h1>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session()->has('error'))
                            <div class="alert alert-danger" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form wire:submit.prevent="save">
                            @include('livewire.inventory.partials.form')
                            <div style="margin-top: 15px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Save Item
                                </button>
                                <a href="{{ route('inventory.index') }}" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>
