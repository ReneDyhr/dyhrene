<div>
    @section('title', 'Print Jobs')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                            <h1 style="margin: 0; flex: 1;">Print Jobs</h1>
                            <a href="{{ route('print-jobs.create') }}" class="btn btn-success" style="color: #fff;">
                                <i class="fa fa-plus"></i> Create Print Job
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
                                placeholder="Search by order no, description, or customer..." style="max-width: 400px;">
                            <select wire:model.live="statusFilter" class="form-control" style="max-width: 200px;">
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="locked">Locked</option>
                            </select>
                            <select wire:model.live="customerFilter" class="form-control" style="max-width: 250px;">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="materialTypeFilter" class="form-control" style="max-width: 250px;">
                                <option value="">All Material Types</option>
                                @foreach($materialTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                            <table class="table" style="width: 100%; min-width: 1200px; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Order No</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Date</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Customer</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Plastnavn</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Type</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">Total Pieces</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">Total Cost</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">Sales Price</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">Profit</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">Profit/Piece</th>
                                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($printJobs as $job)
                                        <tr>
                                            <td style="padding: 8px;">{{ $job->order_no }}</td>
                                            <td style="padding: 8px;">{{ $job->date->format('Y-m-d') }}</td>
                                            <td style="padding: 8px;">{{ $job->customer->name ?? '-' }}</td>
                                            <td style="padding: 8px;">{{ $job->material->name ?? '-' }}</td>
                                            <td style="padding: 8px;">{{ $job->material->materialType->name ?? '-' }}</td>
                                            <td style="padding: 8px;">
                                                @if($job->status === 'draft')
                                                    <span class="badge" style="background-color: #ffc107; color: #000; padding: 4px 8px; border-radius: 4px;">Draft</span>
                                                @else
                                                    <span class="badge" style="background-color: #28a745; color: #fff; padding: 4px 8px; border-radius: 4px;">Locked</span>
                                                @endif
                                            </td>
                                            <td style="padding: 8px; text-align: right;">{{ \App\Support\Format::integer($job->calculation['totals']['total_pieces'] ?? 0) }}</td>
                                            <td style="padding: 8px; text-align: right;">{{ \App\Support\Format::dkk($job->calculation['costs']['total_cost'] ?? 0) }}</td>
                                            <td style="padding: 8px; text-align: right;">{{ \App\Support\Format::dkk($job->calculation['pricing']['sales_price'] ?? 0) }}</td>
                                            <td style="padding: 8px; text-align: right;">{{ \App\Support\Format::dkk($job->calculation['profit']['profit'] ?? 0) }}</td>
                                            <td style="padding: 8px; text-align: right;">{{ \App\Support\Format::dkk($job->calculation['profit']['profit_per_piece'] ?? 0) }}</td>
                                            <td style="padding: 8px;">
                                                <a href="{{ route('print-jobs.show', $job) }}" class="btn btn-info btn-sm"
                                                    style="color: #fff; padding: 4px 10px; font-size: 0.9em; margin-right: 4px;">View</a>
                                                @if($job->isDraft())
                                                    <a href="{{ route('print-jobs.edit', $job) }}" class="btn btn-warning btn-sm"
                                                        style="color: #fff; padding: 4px 10px; font-size: 0.9em; margin-right: 4px;">Edit</a>
                                                    <button wire:confirm="Are you sure you want to delete this print job?"
                                                        wire:click="delete({{ $job->id }})" class="btn btn-danger btn-sm"
                                                        style="color: #fff; padding: 4px 10px; font-size: 0.9em;">Delete</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" style="padding: 20px; text-align: center; color: #777;">
                                                No print jobs found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 15px;">
                            {{ $printJobs->links() }}
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

