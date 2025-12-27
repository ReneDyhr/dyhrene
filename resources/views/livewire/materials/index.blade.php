<div>
    @section('title', 'Materials')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                            <h1 style="margin: 0; flex: 1;">Materials</h1>
                            <a href="{{ route('materials.create') }}" class="btn btn-success" style="color: #fff;">
                                <i class="fa fa-plus"></i> Create Material
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

                        <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                                placeholder="Search by name..." style="max-width: 300px;">
                            <select wire:model.live="materialTypeFilter" class="form-control" style="max-width: 250px;">
                                <option value="">All Material Types</option>
                                @foreach($materialTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Name</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Type</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Price per kg (DKK)</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Waste Factor (%)</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $material)
                                    <tr>
                                        <td style="padding: 8px;">{{ $material->name }}</td>
                                        <td style="padding: 8px;">{{ $material->materialType->name }}</td>
                                        <td style="padding: 8px;">{{ number_format($material->price_per_kg_dkk, 2) }}</td>
                                        <td style="padding: 8px;">{{ number_format($material->waste_factor_pct, 2) }}</td>
                                        <td style="padding: 8px;">
                                            <a href="{{ route('materials.edit', $material) }}" class="btn btn-warning btn-sm"
                                                style="color: #fff; padding: 4px 10px; font-size: 0.9em;">Edit</a>
                                            <button wire:confirm="Are you sure you want to delete this material?"
                                                wire:click="delete({{ $material->id }})" class="btn btn-danger btn-sm"
                                                style="color: #fff; padding: 4px 10px; font-size: 0.9em;">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="padding: 20px; text-align: center; color: #777;">
                                            No materials found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div style="margin-top: 15px;">
                            {{ $materials->links() }}
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>


