<div>
    @section('title', 'Edit Material')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Edit Material</h1>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form wire:submit.prevent="save">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="material_type_id">Material Type <span style="color: red;">*</span></label>
                                <select id="material_type_id" wire:model="material_type_id" class="form-control">
                                    <option value="">Select a material type</option>
                                    @foreach($materialTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('material_type_id')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="name">Name (Plastnavn) <span style="color: red;">*</span></label>
                                <input type="text" id="name" wire:model="name" class="form-control"
                                    placeholder="Material name">
                                @error('name')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="price_per_kg_dkk">Price per kg (DKK) <span style="color: red;">*</span></label>
                                <input type="number" id="price_per_kg_dkk" wire:model="price_per_kg_dkk"
                                    class="form-control" step="0.01" min="0.01" placeholder="0.00">
                                @error('price_per_kg_dkk')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="waste_factor_pct">Waste Factor (%)</label>
                                <input type="number" id="waste_factor_pct" wire:model="waste_factor_pct"
                                    class="form-control" step="0.01" min="0" max="100" placeholder="0">
                                @error('waste_factor_pct')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                                <small class="text-muted" style="font-size: 0.85em;">Default: 0</small>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="notes">Notes</label>
                                <textarea id="notes" wire:model="notes" class="form-control" rows="4"
                                    placeholder="Additional notes..."></textarea>
                                @error('notes')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary" style="color: #fff;">Save</button>
                                <a href="{{ route('print-materials.index') }}" class="btn btn-secondary"
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

