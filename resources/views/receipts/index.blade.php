<div>
    @section('title', 'Receipts')
    @include('components.layouts.sidenav')
    <style>
        @media screen and (max-width: 767px) {
            .receipts-table {
                display: none !important;
            }

            .receipts-mobile {
                display: block !important;
            }

            .month-header {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .month-header>div {
                text-align: left !important;
                margin-top: 4px;
            }
        }

        .receipts-mobile {
            display: none;
        }

        @media screen and (min-width: 768px) {
            .receipts-mobile {
                display: none !important;
            }
        }

        .receipt-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
        }

        .receipt-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .receipt-card-name {
            font-weight: 600;
            font-size: 1em;
            flex: 1;
        }

        .receipt-card-total {
            font-weight: 600;
            color: #28a745;
            font-size: 1em;
        }

        .receipt-card-details {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px;
        }

        .receipt-card-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .receipt-card-actions .btn {
            flex: 1;
            min-width: 60px;
            padding: 5px 8px !important;
            font-size: 0.8em !important;
        }

        .month-header {
            cursor: pointer;
            user-select: none;
        }

        .month-header:hover {
            opacity: 0.8;
        }

        .month-toggle-icon {
            display: inline-block;
            margin-right: 8px;
            transition: transform 0.2s ease;
            font-size: 0.9em;
            color: #666;
        }

        .month-expanded .month-toggle-icon {
            transform: rotate(90deg);
        }

        .month-receipts {
            display: none;
        }

        .month-expanded .month-receipts {
            display: block;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.month-header').forEach(function(header) {
                header.addEventListener('click', function() {
                    const monthSummary = this.closest('.month-summary');
                    monthSummary.classList.toggle('month-expanded');
                });
            });
        });
    </script>
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                            <a href="{{ route('receipts.create') }}" class="btn btn-success mb-2"
                                style="color: #fff;">
                                <i class="fa fa-plus"></i> New Receipt
                            </a>
                            <a href="{{ route('receipts.mass-edit-items') }}" class="btn btn-primary mb-2"
                                style="color: #fff;">
                                <i class="fa fa-edit"></i> Mass Edit Items
                            </a>
                        </div>

                        @foreach($receiptsByMonth as $monthData)
                            <div class="month-summary mb-2"
                                style="border-bottom: 1px solid #e0e0e0; padding-bottom: 12px; margin-bottom: 12px;">
                                <div class="month-header"
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <h3 style="margin: 0; font-size: 1.2em; font-weight: 600; color: #555; display: flex; align-items: center;">
                                        <span class="month-toggle-icon">▶</span>
                                        {{ $monthData['monthName'] }}
                                    </h3>
                                    <div style="text-align: right; font-size: 1em;">
                                        <div style="margin-bottom: 4px;">
                                            <span style="font-weight: 600; color: #28a745;">
                                                {{ number_format($monthData['total'], 2) }} {{ $monthData['currency'] }}
                                            </span>
                                            <span style="color: #999; margin-left: 8px;">
                                                ({{ $monthData['count'] }}
                                                {{ $monthData['count'] === 1 ? 'receipt' : 'receipts' }})
                                            </span>
                                        </div>
                                        @if(!empty($monthData['topCategories']))
                                            <div style="font-size: 0.85em; color: #666; margin-top: 4px;">
                                                @foreach($monthData['topCategories'] as $category)
                                                    <span style="margin-right: 12px;">
                                                        <strong>{{ $category['name'] }}:</strong> 
                                                        {{ number_format($category['total'], 0) }} {{ $monthData['currency'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="month-receipts">
                                <!-- Desktop Table View -->
                                <table class="table receipts-table"
                                    style="table-layout: fixed; width: 100%; margin-bottom: 0;">
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

                                <!-- Mobile Card View -->
                                <div class="receipts-mobile">
                                    @foreach($monthData['receipts'] as $receipt)
                                        <div class="receipt-card">
                                            <div class="receipt-card-header">
                                                <div class="receipt-card-name">{{ $receipt->name }}</div>
                                                <div class="receipt-card-total">{{ number_format($receipt->total, 2) }}
                                                    {{ $receipt->currency }}</div>
                                            </div>
                                            <div class="receipt-card-details">
                                                <div><strong>Vendor:</strong> {{ $receipt->vendor }}</div>
                                                <div><strong>Date:</strong> {{ $receipt->date->format('M j, Y H:i') }}</div>
                                            </div>
                                            <div class="receipt-card-actions">
                                                <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-info btn-sm"
                                                    style="color: #fff;">View</a>
                                                <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-warning btn-sm"
                                                    style="color: #fff;">Edit</a>
                                                <button wire:confirm="Are you sure?"
                                                    wire:click="deleteReceipt({{ $receipt->id }})" class="btn btn-danger btn-sm"
                                                    style="color: #fff;">Delete</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                </div>
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