<div>
    @section('title', 'Customers')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                            <h1 style="margin: 0; flex: 1;">Customers</h1>
                            <a href="{{ route('customers.create') }}" class="btn btn-success" style="color: #fff;">
                                <i class="fa fa-plus"></i> Create Customer
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

                        <div style="margin-bottom: 15px;">
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                                placeholder="Search by name or email..." style="max-width: 400px;">
                        </div>

                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Name</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Email</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Phone</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    <tr>
                                        <td style="padding: 8px;">{{ $customer->name }}</td>
                                        <td style="padding: 8px;">{{ $customer->email ?? '-' }}</td>
                                        <td style="padding: 8px;">{{ $customer->phone ?? '-' }}</td>
                                        <td style="padding: 8px;">
                                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm"
                                                style="color: #fff; padding: 4px 10px; font-size: 0.9em;">Edit</a>
                                            <button wire:confirm="Are you sure you want to delete this customer?"
                                                wire:click="delete({{ $customer->id }})" class="btn btn-danger btn-sm"
                                                style="color: #fff; padding: 4px 10px; font-size: 0.9em;">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="padding: 20px; text-align: center; color: #777;">
                                            No customers found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div style="margin-top: 15px;">
                            {{ $customers->links() }}
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

