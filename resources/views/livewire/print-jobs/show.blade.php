<div>
    @section('title', 'Print Job: ' . $printJob->order_no)
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                            <h1 style="margin: 0; flex: 1;">Print Job: {{ $printJob->order_no }}</h1>
                            @if($printJob->isDraft())
                                <a href="{{ route('print-jobs.edit', $printJob) }}" class="btn btn-warning" style="color: #fff;">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            @else
                                <button wire:click="unlock" class="btn btn-warning" style="color: #fff;" disabled>
                                    <i class="fa fa-unlock"></i> Unlock (Placeholder)
                                </button>
                            @endif
                            <a href="{{ route('print-jobs.index') }}" class="btn btn-secondary" style="color: #fff;">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>

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

                        <!-- Status Badge -->
                        <div style="margin-bottom: 20px;">
                            @if($printJob->status === 'draft')
                                <span class="badge" style="background-color: #ffc107; color: #000; padding: 8px 16px; border-radius: 4px; font-size: 1em;">
                                    <i class="fa fa-file"></i> Draft / Not Locked
                                </span>
                            @else
                                <span class="badge" style="background-color: #28a745; color: #fff; padding: 8px 16px; border-radius: 4px; font-size: 1em;">
                                    <i class="fa fa-lock"></i> Locked
                                </span>
                                @if($printJob->locked_at)
                                    <span style="margin-left: 10px; color: #777;">
                                        Locked at: {{ $printJob->locked_at->format('Y-m-d H:i:s') }}
                                    </span>
                                @endif
                            @endif
                        </div>

                        <!-- Job Information -->
                        <div style="background-color: #f9f9f9; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                            <h2 style="margin-top: 0; margin-bottom: 15px;">Job Information</h2>
                            
                            @if($printJob->isLocked())
                                <!-- Admin fields editable for locked jobs -->
                                <form wire:submit.prevent="saveAdminFields">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                        <div class="form-group">
                                            <label for="date">Date <span style="color: red;">*</span></label>
                                            <input type="date" id="date" wire:model="date" class="form-control">
                                            @error('date')
                                                <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="customer_id">Customer <span style="color: red;">*</span></label>
                                            <select id="customer_id" wire:model="customer_id" class="form-control">
                                                @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('customer_id')
                                                <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label for="description">Description <span style="color: red;">*</span></label>
                                        <textarea id="description" wire:model="description" class="form-control" rows="3"></textarea>
                                        @error('description')
                                            <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label for="internal_notes">Internal Notes</label>
                                        <textarea id="internal_notes" wire:model="internal_notes" class="form-control" rows="4"></textarea>
                                        @error('internal_notes')
                                            <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary" style="color: #fff;">Save Admin Fields</button>
                                </form>
                            @else
                                <!-- Read-only display for draft jobs -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <strong>Date:</strong> {{ $printJob->date->format('Y-m-d') }}
                                    </div>
                                    <div>
                                        <strong>Customer:</strong> {{ $printJob->customer->name ?? '-' }}
                                    </div>
                                </div>
                                <div style="margin-top: 15px;">
                                    <strong>Description:</strong><br>
                                    {{ $printJob->description }}
                                </div>
                                @if($printJob->internal_notes)
                                    <div style="margin-top: 15px;">
                                        <strong>Internal Notes:</strong><br>
                                        {{ $printJob->internal_notes }}
                                    </div>
                                @endif
                            @endif

                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                    <div>
                                        <strong>Material (Plastnavn):</strong> {{ $printJob->material->name ?? '-' }}
                                    </div>
                                    <div>
                                        <strong>Type:</strong> {{ $printJob->material->materialType->name ?? '-' }}
                                    </div>
                                    <div>
                                        <strong>Pieces per Plate:</strong> {{ $printJob->pieces_per_plate }}
                                    </div>
                                    <div>
                                        <strong>Plates:</strong> {{ $printJob->plates }}
                                    </div>
                                    <div>
                                        <strong>Grams per Plate:</strong> {{ \App\Support\Format::number($printJob->grams_per_plate) }}
                                    </div>
                                    <div>
                                        <strong>Hours per Plate:</strong> {{ \App\Support\Format::number($printJob->hours_per_plate, 3) }}
                                    </div>
                                    <div>
                                        <strong>Labor Hours:</strong> {{ \App\Support\Format::number($printJob->labor_hours, 3) }}
                                    </div>
                                    <div>
                                        <strong>Is First Time Order:</strong> {{ $printJob->is_first_time_order ? 'Yes' : 'No' }}
                                    </div>
                                    @if($printJob->avance_pct_override !== null)
                                        <div>
                                            <strong>Avance % Override:</strong> {{ \App\Support\Format::pct($printJob->avance_pct_override) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Calculation Results -->
                        <div style="background-color: #f0f8ff; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                            <h2 style="margin-top: 0; margin-bottom: 15px;">
                                Calculation Results
                                @if($printJob->isDraft())
                                    <span style="font-size: 0.8em; color: #777; font-weight: normal;">(Draft/Not Locked - Computed Live)</span>
                                @endif
                            </h2>

                            @if($calculation)
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
                                    <div>
                                        <h3 style="font-size: 1.1em; margin-bottom: 10px;">Totals</h3>
                                        <div style="background-color: #fff; padding: 15px; border-radius: 4px;">
                                            <div style="margin-bottom: 8px;"><strong>Total Pieces:</strong> {{ \App\Support\Format::integer($calculation['totals']['total_pieces'] ?? 0) }}</div>
                                            <div style="margin-bottom: 8px;"><strong>Total Grams:</strong> {{ \App\Support\Format::number($calculation['totals']['total_grams'] ?? 0) }}</div>
                                            <div style="margin-bottom: 8px;"><strong>Total Print Hours:</strong> {{ \App\Support\Format::number($calculation['totals']['total_print_hours'] ?? 0, 3) }}</div>
                                            <div><strong>kWh:</strong> {{ \App\Support\Format::number($calculation['totals']['kwh'] ?? 0, 2) }}</div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 style="font-size: 1.1em; margin-bottom: 10px;">Costs</h3>
                                        <div style="background-color: #fff; padding: 15px; border-radius: 4px;">
                                            <div style="margin-bottom: 8px;"><strong>Material Cost:</strong> {{ \App\Support\Format::dkk($calculation['costs']['material_cost'] ?? 0) }}</div>
                                            <div style="margin-bottom: 8px;"><strong>Material Cost (with Waste):</strong> {{ \App\Support\Format::dkk($calculation['costs']['material_cost_with_waste'] ?? 0) }}</div>
                                            <div style="margin-bottom: 8px;"><strong>Power Cost:</strong> {{ \App\Support\Format::dkk($calculation['costs']['power_cost'] ?? 0) }}</div>
                                            <div style="margin-bottom: 8px;"><strong>Labor Cost:</strong> {{ \App\Support\Format::dkk($calculation['costs']['labor_cost'] ?? 0) }}</div>
                                            <div style="margin-bottom: 8px;"><strong>First Time Fee:</strong> {{ \App\Support\Format::dkk($calculation['costs']['first_time_fee_applied'] ?? 0) }}</div>
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;"><strong>Total Cost:</strong> {{ \App\Support\Format::dkk($calculation['costs']['total_cost'] ?? 0) }}</div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 style="font-size: 1.1em; margin-bottom: 10px;">Pricing</h3>
                                        <div style="background-color: #fff; padding: 15px; border-radius: 4px;">
                                            <div style="margin-bottom: 8px;"><strong>Applied Avance %:</strong> {{ \App\Support\Format::pct($calculation['pricing']['applied_avance_pct'] ?? 0) }}</div>
                                            <div style="margin-bottom: 8px;"><strong>Sales Price:</strong> {{ \App\Support\Format::dkk($calculation['pricing']['sales_price'] ?? 0) }}</div>
                                            <div><strong>Price per Piece:</strong> {{ \App\Support\Format::dkk($calculation['pricing']['price_per_piece'] ?? 0) }}</div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 style="font-size: 1.1em; margin-bottom: 10px;">Profit</h3>
                                        <div style="background-color: #fff; padding: 15px; border-radius: 4px;">
                                            <div style="margin-bottom: 8px;"><strong>Profit:</strong> {{ \App\Support\Format::dkk($calculation['profit']['profit'] ?? 0) }}</div>
                                            <div><strong>Profit per Piece:</strong> {{ \App\Support\Format::dkk($calculation['profit']['profit_per_piece'] ?? 0) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p style="color: #777;">No calculation data available.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

