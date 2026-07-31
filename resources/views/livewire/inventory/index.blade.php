<div>
    @section('title', $title)
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                            <h1 style="margin: 0; flex: 1;">Inventory</h1>
                            <a href="{{ route('inventory.create') }}" class="btn btn-success" style="color: #fff;">
                                <i class="fa fa-plus"></i> Add Item
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
                                placeholder="Search by name, brand, model, or serial..." style="max-width: 400px;">
                            <select wire:model.live="categoryFilter" class="form-control" style="max-width: 200px;">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="statusFilter" class="form-control" style="max-width: 200px;">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            <a href="{{ route('inventory.categories') }}" class="btn btn-default">
                                <i class="fa fa-folder"></i> Manage Categories
                            </a>
                        </div>

                        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                            <table class="table" style="width: 100%; min-width: 1400px; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Category</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Item</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Brand</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Model</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Serial Number</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Owner</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">Price</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">Current Value</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Acq. Type</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Acq. Date</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">From</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Status Date</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Reason</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($items as $item)
                                        <tr>
                                            <td style="padding: 8px;">{{ $item->category?->name }}</td>
                                            <td style="padding: 8px;">{{ $item->name }}</td>
                                            <td style="padding: 8px;">{{ $item->brand ?: '-' }}</td>
                                            <td style="padding: 8px;">{{ $item->model ?: '-' }}</td>
                                            <td style="padding: 8px;">{{ $item->serial_number ?: '-' }}</td>
                                            <td style="padding: 8px;">{{ $item->owner->label() }}</td>
                                            <td style="padding: 8px; text-align: right;">
                                                {{ $item->price !== null ? \number_format((float) $item->price, 2) : '-' }}
                                            </td>
                                            <td style="padding: 8px; text-align: right;">
                                                {{ $item->current_value !== null ? \number_format((float) $item->current_value, 2) : '-' }}
                                            </td>
                                            <td style="padding: 8px;">{{ $item->acquisition_type?->label() ?? '-' }}</td>
                                            <td style="padding: 8px;">{{ $item->acquisition_date?->format('Y-m-d') ?? '-' }}</td>
                                            <td style="padding: 8px;">{{ $item->acquired_from ?: '-' }}</td>
                                            <td style="padding: 8px;">{{ $item->status->label() }}</td>
                                            <td style="padding: 8px;">{{ $item->status_change_date?->format('Y-m-d') ?? '-' }}</td>
                                            <td style="padding: 8px;">{{ $item->status_reason ?: '-' }}</td>
                                            <td style="padding: 8px;">
                                                <a href="{{ route('inventory.show', $item) }}" class="btn btn-info btn-sm"
                                                    style="color: #fff; padding: 4px 10px; font-size: 0.9em; margin-right: 4px;">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('inventory.edit', $item) }}" class="btn btn-warning btn-sm"
                                                    style="color: #fff; padding: 4px 10px; font-size: 0.9em; margin-right: 4px;">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                                <button wire:confirm="Are you sure you want to delete this item?"
                                                    wire:click="delete({{ $item->id }})" class="btn btn-danger btn-sm"
                                                    style="color: #fff; padding: 4px 10px; font-size: 0.9em;">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="15" style="padding: 20px; text-align: center; color: #777;">
                                                No items found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 15px;">
                            {{ $items->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>
