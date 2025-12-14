<div>
    @section('title', 'Receipts')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <a href="{{ route('receipts.create') }}" class="btn btn-success mb-2"
                            style="color: #fff; margin-bottom: 12px;">
                            <i class="fa fa-plus"></i> New Receipt
                        </a>

                        @foreach($receiptsByMonth as $monthData)
                            <div class="month-summary mb-2"
                                style="border-bottom: 1px solid #e0e0e0; padding-bottom: 12px; margin-bottom: 12px;">
                                <div class="month-header"
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <h3 style="margin: 0; font-size: 1.2em; font-weight: 600; color: #555;">
                                        {{ $monthData['monthName'] }}
                                    </h3>
                                    <div style="text-align: right; font-size: 1em;">
                                        <span style="font-weight: 600; color: #28a745;">
                                            {{ number_format($monthData['total'], 2) }} {{ $monthData['currency'] }}
                                        </span>
                                        <span style="color: #999; margin-left: 8px;">
                                            ({{ $monthData['count'] }}
                                            {{ $monthData['count'] === 1 ? 'receipt' : 'receipts' }})
                                        </span>
                                    </div>
                                </div>

                                <table class="table" style="table-layout: fixed; width: 100%; margin-bottom: 0;">
                                    <colgroup>
                                        <col style="width: 25%;">
                                        <col style="width: 20%;">
                                        <col style="width: 15%;">
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th
                                                style="padding: 4px 8px; font-size: 1em; font-weight: 600; border-bottom: 1px solid #ddd;">
                                                Name</th>
                                            <th
                                                style="padding: 4px 8px; font-size: 1em; font-weight: 600; border-bottom: 1px solid #ddd;">
                                                Vendor</th>
                                            <th
                                                style="padding: 4px 8px; font-size: 1em; font-weight: 600; border-bottom: 1px solid #ddd;">
                                                Total</th>
                                            <th
                                                style="padding: 4px 8px; font-size: 1em; font-weight: 600; border-bottom: 1px solid #ddd;">
                                                Date</th>
                                            <th
                                                style="padding: 4px 8px; font-size: 1em; font-weight: 600; border-bottom: 1px solid #ddd;">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($monthData['receipts'] as $receipt)
                                            <tr>
                                                <td
                                                    style="word-wrap: break-word; overflow-wrap: break-word; padding: 4px 8px; font-size: 1em;">
                                                    {{ $receipt->name }}
                                                </td>
                                                <td
                                                    style="word-wrap: break-word; overflow-wrap: break-word; padding: 4px 8px; font-size: 1em;">
                                                    {{ $receipt->vendor }}
                                                </td>
                                                <td style="padding: 4px 8px; font-size: 1em;">
                                                    {{ number_format($receipt->total, 2) }} {{ $receipt->currency }}
                                                </td>
                                                <td style="padding: 4px 8px; font-size: 1em;">
                                                    {{ $receipt->date->format('M j, Y H:i') }}
                                                </td>
                                                <td style="padding: 4px 8px;">
                                                    <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-info btn-sm"
                                                        style="color: #fff; padding: 4px 10px; font-size: 0.9em; line-height: 1.4;">View</a>
                                                    <a href="{{ route('receipts.edit', $receipt) }}"
                                                        class="btn btn-warning btn-sm"
                                                        style="color: #fff; padding: 4px 10px; font-size: 0.9em; line-height: 1.4;">Edit</a>
                                                    <button wire:confirm="Are you sure?"
                                                        wire:click="deleteReceipt({{ $receipt->id }})"
                                                        class="btn btn-danger btn-sm"
                                                        style="color: #fff; padding: 4px 10px; font-size: 0.9em; line-height: 1.4;">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach

                        @if($receiptsByMonth->isEmpty())
                            <div class="alert alert-info" style="padding: 15px; margin-top: 20px;">
                                No receipts found.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>