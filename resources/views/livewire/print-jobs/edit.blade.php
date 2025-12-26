<div>
    @section('title', 'Edit Print Job')
    <style>
        @media (max-width: 768px) {
            .calculation-panel > div[style*="grid"] {
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
                        <h1>Edit Print Job: {{ $printJob->order_no }}</h1>

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

                        <form wire:submit.prevent="save" x-data="{ 
                            formChanged: false,
                            init() {
                                const form = this.$el;
                                
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
                                    const id = @entangle('material_id');
                                    return this.materials.find(m => m.id == id);
                                },
                                selectMaterial(material) {
                                    @this.set('material_id', material.id);
                                    this.searchTerm = '';
                                    this.open = false;
                                }
                            }">
                                <label for="material_id">Material (Plastnavn) <span style="color: red;">*</span></label>
                                <div style="position: relative;">
                                    <input 
                                        type="text" 
                                        x-model="searchTerm"
                                        @focus="open = true"
                                        @click="open = true"
                                        @keydown.escape="open = false"
                                        :value="selectedMaterial ? selectedMaterial.name + ' (' + selectedMaterial.type + ')' : ''"
                                        placeholder="Search and select a material..."
                                        class="form-control"
                                        style="cursor: pointer;">
                                    <div x-show="open" 
                                         @click.away="open = false"
                                         x-transition
                                         style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2px;">
                                        <template x-for="material in filteredMaterials" :key="material.id">
                                            <div 
                                                @click="selectMaterial(material)"
                                                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #eee;"
                                                :style="selectedMaterial && selectedMaterial.id == material.id ? 'background-color: #007bff; color: white;' : ''"
                                                x-on:mouseenter="$el.style.backgroundColor = selectedMaterial && selectedMaterial.id == material.id ? '#007bff' : '#f5f5f5'"
                                                x-on:mouseleave="$el.style.backgroundColor = selectedMaterial && selectedMaterial.id == material.id ? '#007bff' : 'white'">
                                                <strong x-text="material.name"></strong> <span style="color: #666;" x-text="'(' + material.type + ')'"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredMaterials.length === 0" style="padding: 15px; text-align: center; color: #777;">
                                            No materials found
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" wire:model.debounce.500ms="material_id">
                                @error('material_id')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="pieces_per_plate">Pieces per Plate <span style="color: red;">*</span></label>
                                <input type="number" id="pieces_per_plate" wire:model.debounce.500ms="pieces_per_plate" class="form-control"
                                    min="1" max="100">
                                @error('pieces_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="plates">Plates <span style="color: red;">*</span></label>
                                <input type="number" id="plates" wire:model.debounce.500ms="plates" class="form-control"
                                    min="1" max="10">
                                @error('plates')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="grams_per_plate">Grams per Plate <span style="color: red;">*</span></label>
                                <input type="number" id="grams_per_plate" wire:model.debounce.500ms="grams_per_plate" class="form-control"
                                    step="0.01" min="0" max="999" placeholder="0.00" inputmode="decimal">
                                @error('grams_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="hours_per_plate">Hours per Plate <span style="color: red;">*</span></label>
                                <input type="number" id="hours_per_plate" wire:model.debounce.500ms="hours_per_plate" class="form-control"
                                    step="0.001" min="0" max="999" placeholder="0.000" inputmode="decimal">
                                @error('hours_per_plate')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="labor_hours">Labor Hours <span style="color: red;">*</span></label>
                                <input type="number" id="labor_hours" wire:model.debounce.500ms="labor_hours" class="form-control"
                                    step="0.001" min="0" max="999" placeholder="0.000" inputmode="decimal">
                                @error('labor_hours')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>
                                    <input type="checkbox" wire:model.debounce.500ms="is_first_time_order">
                                    Is First Time Order
                                </label>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="avance_pct_override">Avance % Override</label>
                                <input type="number" id="avance_pct_override" wire:model.debounce.500ms="avance_pct_override" class="form-control"
                                    step="0.01" min="0" max="1000" placeholder="Leave empty to use default">
                                @error('avance_pct_override')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary" style="color: #fff;">Save</button>
                                <button type="button" wire:click="lock" 
                                    wire:confirm="Are you sure you want to lock this job? Once locked, calculation inputs cannot be changed."
                                    class="btn btn-warning" style="color: #fff;">
                                    <i class="fa fa-lock"></i> Lock
                                </button>
                                <a href="{{ route('print-jobs.show', $printJob) }}" class="btn btn-secondary"
                                    style="color: #fff;">Cancel</a>
                            </div>
                        </form>

                        <!-- Calculation Panel -->
                        @livewire('print-jobs.components.calculation-panel', [
                            'printJob' => $printJob,
                            'isLocked' => $printJob->isLocked(),
                        ], key('calc-panel-edit-' . $printJob->id))
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

