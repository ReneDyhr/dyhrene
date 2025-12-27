<div>
    @section('title', 'Material Types')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                            <h1 style="margin: 0; flex: 1;">Material Types</h1>
                            <a href="{{ route('material-types.create') }}" class="btn btn-success" style="color: #fff;">
                                <i class="fa fa-plus"></i> Create Material Type
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

                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Name</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Avg kWh per Hour</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materialTypes as $materialType)
                                    <tr>
                                        <td style="padding: 8px;">{{ $materialType->name }}</td>
                                        <td style="padding: 8px;">{{ number_format($materialType->avg_kwh_per_hour, 4) }}</td>
                                        <td style="padding: 8px;">
                                            <a href="{{ route('material-types.edit', $materialType) }}"
                                                class="btn btn-warning btn-sm" style="color: #fff; padding: 4px 10px; font-size: 0.9em;">Edit</a>
                                            <button wire:confirm="Are you sure you want to delete this material type?"
                                                wire:click="delete({{ $materialType->id }})" class="btn btn-danger btn-sm"
                                                style="color: #fff; padding: 4px 10px; font-size: 0.9em;">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="padding: 20px; text-align: center; color: #777;">
                                            No material types found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>


