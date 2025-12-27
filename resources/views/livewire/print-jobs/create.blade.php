<div>
    @section('title', 'Create Print Job')
    <style>
        @media (max-width: 768px) {
            .calculation-panel>div[style*="grid"] {
                grid-template-columns: 1fr !important;
            }

            .form-group {
                margin-bottom: 12px !important;
            }
        }
    </style>
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

                        <form wire:submit.prevent="save" x-data="{ 
                            formChanged: false,
                            init() {
                                const form = this.$el;
                                const initialData = new FormData(form);
                                
                                // Track changes
                                form.addEventListener('input', () => {
                                    this.formChanged = true;
                                });
                                
                                form.addEventListener('change', () => {
                                    this.formChanged = true;
                                });
                                
                                // Reset on successful save
                                window.addEventListener('livewire:init', () => {
                                    Livewire.hook('morph.updated', () => {
                                        if (document.querySelector('.alert-success')) {
                                            this.formChanged = false;
                                        }
                                    });
                                });
                                
                                // Warn on navigation
                                window.addEventListener('beforeunload', (e) => {
                                    if (this.formChanged) {
                                        e.preventDefault();
                                        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                                        return e.returnValue;
                                    }
                                });
                                
                                // Warn on link clicks
                                document.querySelectorAll('a').forEach(link => {
                                    link.addEventListener('click', (e) => {
                                        if (this.formChanged && !link.hasAttribute('wire:ignore')) {
                                            if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
                                                e.preventDefault();
                                            } else {
                                                this.formChanged = false;
                                            }
                                        }
                                    });
                                });
                            }
                        }">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="date">Date <span style="color: red;">*</span></label>
                                <input type="date" id="date" wire:model="date" class="form-control" required>
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

                            <div class="form-group" style="margin-bottom: 15px;" x-data="{
                                searchTerm: '',
                                open: false,
                                selectedMaterialId: @js($material_id),
                                materials: [
                                    @foreach($materialTypes as $type)
                                        @foreach($materials->get($type->id, []) as $material)
                                            { id: {{ $material->id }}, name: '{{ addslashes($material->name) }}', type: '{{ addslashes($type->name) }}' },
                                        @endforeach
                                    @endforeach
                                ],
                                get filteredMaterials() {
                                    if (!this.searchTerm) return this.materials;
                                    const term = this.searchTerm.toLowerCase();
                                    return this.materials.filter(m => 
                                        m.name.toLowerCase().includes(term) || 
                                        m.type.toLowerCase().includes(term)
                                    );
                                },
                                get selectedMaterial() {
                                    if (!this.selectedMaterialId) return null;
                                    return this.materials.find(m => m.id == this.selectedMaterialId);
                                },
                                get displayValue() {
                                    const selected = this.selectedMaterial;
                                    if (selected) {
                                        return selected.name + ' (' + selected.type + ')';
                                    }
                                    return '';
                                },
                                selectMaterial(material) {
                                    this.selectedMaterialId = material.id;
                                    this.searchTerm = '';
                                    this.open = false;
                                    // Update Livewire directly
                                    @this.set('material_id', material.id, true);
                                }
                            }" x-init="$watch('selectedMaterialId', value => {
                                if (value) {
                                    @this.set('material_id', value, true);
                                }
                            })">
                                <label for="material_id">Material (Plastnavn) <span style="color: red;">*</span></label>
                                <div style="position: relative;">
                                    <input type="text" x-model="searchTerm" @focus="open = true" @click="open = true"
                                        @keydown.escape="open = false" :value="displayValue"
                                        @input="if (!searchTerm) { $event.target.value = displayValue; }"
                                        placeholder="Search and select a material..." class="form-control"
                                        style="cursor: pointer;">
                                    <div x-show="open" @click.away="open = false" x-transition
                                        style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2px;">
                                        <template x-for="material in filteredMaterials" :key="material.id">
                                            <div @click="selectMaterial(material)"
                                                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #eee;"
                                                :style="selectedMaterial && selectedMaterial.id == material.id ? 'background-color: #007bff; color: white;' : ''"
                                                x-on:mouseenter="$el.style.backgroundColor = selectedMaterial && selectedMaterial.id == material.id ? '#007bff' : '#f5f5f5'"
                                                x-on:mouseleave="$el.style.backgroundColor = selectedMaterial && selectedMaterial.id == material.id ? '#007bff' : 'white'">
                                                <strong x-text="material.name"></strong> <span style="color: #666;"
                                                    x-text="'(' + material.type + ')'"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredMaterials.length === 0"
                                            style="padding: 15px; text-align: center; color: #777;">
                                            No materials found
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" wire:model.live="material_id">
                                @error('material_id')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="pieces_per_plate">Pieces per Plate <span
                                        style="color: red;">*</span></label>
                                <input type="number" id="pieces_per_plate" wire:model.live.debounce.500ms="pieces_per_plate"
                                    class="form-control" min="1" max="100">
                                @error('pieces_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="plates">Plates <span style="color: red;">*</span></label>
                                <input type="number" id="plates" wire:model.live.debounce.500ms="plates" class="form-control"
                                    min="1" max="10">
                                @error('plates')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="grams_per_plate">Grams per Plate <span style="color: red;">*</span></label>
                                <input type="number" id="grams_per_plate" wire:model.live.debounce.500ms="grams_per_plate"
                                    class="form-control" step="0.01" min="0" max="999" placeholder="0.00"
                                    inputmode="decimal">
                                @error('grams_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>Hours per Plate <span style="color: red;">*</span></label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <div style="flex: 1;">
                                        <label for="hours_per_plate_hours" style="font-size: 0.9em; color: #666; display: block; margin-bottom: 4px;">Hours</label>
                                        <input type="number" id="hours_per_plate_hours" wire:model.live.debounce.500ms="hours_per_plate_hours"
                                            class="form-control" placeholder="0" min="0" max="999" step="1">
                                        @error('hours_per_plate_hours')
                                            <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div style="flex: 1;">
                                        <label for="hours_per_plate_minutes" style="font-size: 0.9em; color: #666; display: block; margin-bottom: 4px;">Minutes</label>
                                        <input type="number" id="hours_per_plate_minutes" wire:model.live.debounce.500ms="hours_per_plate_minutes"
                                            class="form-control" placeholder="0" min="0" max="59" step="1">
                                        @error('hours_per_plate_minutes')
                                            <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                @error('hours_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="labor_hours">Labor Hours <span style="color: red;">*</span></label>
                                <input type="number" id="labor_hours" wire:model.live.debounce.500ms="labor_hours"
                                    class="form-control" step="0.001" min="0" max="999" value="0" placeholder="0.000"
                                    inputmode="decimal">
                                @error('labor_hours')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>
                                    <input type="checkbox" wire:model.live="is_first_time_order">
                                    Is First Time Order
                                </label>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="avance_pct_override">Avance % Override</label>
                                <input type="number" id="avance_pct_override"
                                    wire:model.live.debounce.500ms="avance_pct_override" class="form-control" step="0.01"
                                    min="0" max="1000" placeholder="Leave empty to use default">
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

                        <!-- Calculation Panel -->
                        @php
                            use App\Support\Format;
                            $calc = $calculation ?? null;
                            $isLocked = false;
                        @endphp
                        <div class="calculation-panel"
                            wire:key="calc-panel-{{ $material_id ?? '0' }}-{{ $pieces_per_plate }}-{{ $plates }}-{{ (int) ($grams_per_plate * 100) }}-{{ (int) ($hours_per_plate * 1000) }}-{{ (int) ($labor_hours * 1000) }}"
                            style="margin-top: 30px; padding: 20px; background-color: #f0f8ff; border-radius: 4px; border: 2px solid {{ $isLocked ? '#28a745' : '#ffc107' }};">
                            <div
                                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                                <h2 style="margin: 0; font-size: 1.3em;">
                                    Calculation Results
                                    <span class="badge"
                                        style="background-color: #ffc107; color: #000; padding: 4px 12px; border-radius: 4px; font-size: 0.7em; margin-left: 10px;">
                                        <i class="fa fa-file"></i> Draft
                                    </span>
                                </h2>
                            </div>

                            @if($calc)
                                <div
                                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                    <!-- Totals Section -->
                                    <div class="calculation-section"
                                        style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <h3
                                            style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px;">
                                            Totals</h3>
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <div>
                                                <strong style="color: #666;">Total Pieces:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::integer((int) ($calc['totals']['total_pieces'] ?? 0)) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">Total Grams:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::number($calc['totals']['total_grams'] ?? 0) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">Total Print Hours:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::number($calc['totals']['total_print_hours'] ?? 0, 3) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">kWh:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::number($calc['totals']['kwh'] ?? 0, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Costs Section -->
                                    <div class="calculation-section"
                                        style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <h3
                                            style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 8px;">
                                            Costs</h3>
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <div>
                                                <strong style="color: #666;">Material Cost:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::dkk($calc['costs']['material_cost'] ?? 0) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">Material Cost (with Waste):</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::dkk($calc['costs']['material_cost_with_waste'] ?? 0) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">Power Cost:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::dkk($calc['costs']['power_cost'] ?? 0) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">Labor Cost:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::dkk($calc['costs']['labor_cost'] ?? 0) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">First Time Fee:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::dkk($calc['costs']['first_time_fee_applied'] ?? 0) }}</div>
                                            </div>
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #ddd;">
                                                <strong style="color: #333; font-size: 1.1em;">Total Cost:</strong>
                                                <div
                                                    style="font-size: 1.3em; color: #dc3545; font-weight: bold; margin-top: 4px;">
                                                    {{ Format::dkk($calc['costs']['total_cost'] ?? 0) }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pricing Section -->
                                    <div class="calculation-section"
                                        style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <h3
                                            style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #28a745; padding-bottom: 8px;">
                                            Pricing</h3>
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <div>
                                                <strong style="color: #666;">Applied Avance %:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::pct($calc['pricing']['applied_avance_pct'] ?? 0) }}</div>
                                            </div>
                                            <div>
                                                <strong style="color: #666;">Price per Piece:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::dkk($calc['pricing']['price_per_piece'] ?? 0) }}</div>
                                            </div>
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #ddd;">
                                                <strong style="color: #333; font-size: 1.1em;">Sales Price:</strong>
                                                <div
                                                    style="font-size: 1.3em; color: #28a745; font-weight: bold; margin-top: 4px;">
                                                    {{ Format::dkk($calc['pricing']['sales_price'] ?? 0) }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Profit Section -->
                                    <div class="calculation-section"
                                        style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <h3
                                            style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #17a2b8; padding-bottom: 8px;">
                                            Profit</h3>
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <div>
                                                <strong style="color: #666;">Profit:</strong>
                                                <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                                                    {{ Format::dkk($calc['profit']['profit'] ?? 0) }}</div>
                                            </div>
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #ddd;">
                                                <strong style="color: #333; font-size: 1.1em;">Profit per Piece:</strong>
                                                <div
                                                    style="font-size: 1.3em; color: #17a2b8; font-weight: bold; margin-top: 4px;">
                                                    {{ Format::dkk($calc['profit']['profit_per_piece'] ?? 0) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div style="padding: 20px; text-align: center; color: #777;">
                                    <p style="margin: 0;">No calculation data available. Please fill in the required fields
                                        (especially Material).</p>
                                    @if(empty($material_id))
                                        <p style="margin: 5px 0 0 0; font-size: 0.9em;">Material is required for calculation.
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>