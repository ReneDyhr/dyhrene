<div>
    @section('title', 'Create Material Type')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Create Material Type</h1>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form wire:submit.prevent="save">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="name">Name <span style="color: red;">*</span></label>
                                <input type="text" id="name" wire:model="name" class="form-control"
                                    placeholder="e.g., PLA, PETG, TPU">
                                @error('name')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="avg_kwh_per_hour">Average kWh per Hour <span style="color: red;">*</span></label>
                                <input type="number" id="avg_kwh_per_hour" wire:model="avg_kwh_per_hour"
                                    class="form-control" step="0.0001" min="0.0001" placeholder="0.0001">
                                @error('avg_kwh_per_hour')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                                <small class="text-muted" style="font-size: 0.85em;">Must be greater than 0.0001</small>
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary" style="color: #fff;">Save</button>
                                <a href="{{ route('material-types.index') }}" class="btn btn-secondary"
                                    style="color: #fff;">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>


