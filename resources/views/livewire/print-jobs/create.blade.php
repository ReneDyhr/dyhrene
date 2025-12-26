<div>
    @section('title', 'Create Print Job')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Create Print Job</h1>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form wire:submit.prevent="save">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="date">Date <span style="color: red;">*</span></label>
                                <input type="date" id="date" wire:model="date" class="form-control">
                                @error('date')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="description">Description <span style="color: red;">*</span></label>
                                <textarea id="description" wire:model="description" class="form-control" rows="3"
                                    placeholder="Job description"></textarea>
                                @error('description')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="internal_notes">Internal Notes</label>
                                <textarea id="internal_notes" wire:model="internal_notes" class="form-control" rows="4"
                                    placeholder="Internal notes (optional)"></textarea>
                                @error('internal_notes')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="customer_id">Customer <span style="color: red;">*</span></label>
                                <select id="customer_id" wire:model="customer_id" class="form-control">
                                    <option value="">Select a customer...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="material_id">Material (Plastnavn) <span style="color: red;">*</span></label>
                                <select id="material_id" wire:model="material_id" class="form-control">
                                    <option value="">Select a material...</option>
                                    @foreach($materialTypes as $type)
                                        <optgroup label="{{ $type->name }}">
                                            @foreach($materials->get($type->id, []) as $material)
                                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('material_id')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="pieces_per_plate">Pieces per Plate <span style="color: red;">*</span></label>
                                <input type="number" id="pieces_per_plate" wire:model="pieces_per_plate" class="form-control"
                                    min="1" max="100">
                                @error('pieces_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="plates">Plates <span style="color: red;">*</span></label>
                                <input type="number" id="plates" wire:model="plates" class="form-control"
                                    min="1" max="10">
                                @error('plates')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="grams_per_plate">Grams per Plate <span style="color: red;">*</span></label>
                                <input type="number" id="grams_per_plate" wire:model="grams_per_plate" class="form-control"
                                    step="0.01" min="0" max="999">
                                @error('grams_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="hours_per_plate">Hours per Plate <span style="color: red;">*</span></label>
                                <input type="number" id="hours_per_plate" wire:model="hours_per_plate" class="form-control"
                                    step="0.001" min="0" max="999">
                                @error('hours_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="labor_hours">Labor Hours <span style="color: red;">*</span></label>
                                <input type="number" id="labor_hours" wire:model="labor_hours" class="form-control"
                                    step="0.001" min="0" max="999" value="0">
                                @error('labor_hours')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>
                                    <input type="checkbox" wire:model="is_first_time_order">
                                    Is First Time Order
                                </label>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="avance_pct_override">Avance % Override</label>
                                <input type="number" id="avance_pct_override" wire:model="avance_pct_override" class="form-control"
                                    step="0.01" min="0" max="1000" placeholder="Leave empty to use default">
                                @error('avance_pct_override')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary" style="color: #fff;">Save</button>
                                <a href="{{ route('print-jobs.index') }}" class="btn btn-secondary"
                                    style="color: #fff;">Cancel</a>
                            </div>
                        </form>

                        <!-- Placeholder for calculation panel (will be added in Phase 7) -->
                        <div style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 4px;">
                            <p style="color: #777; font-style: italic;">Calculation panel will be added in Phase 7</p>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

