<div>
    @section("title", "Item: " . $item->name)
    @include("components.layouts.sidenav")
    <div id="main">
        @include("components.layouts.header")
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>{{ $item->name }}</h1>

                        <div class="description" style="margin-bottom: 20px;">
                            <strong>Brand:</strong> {{ $item->brand ?: "-" }}<br />
                            <strong>Model:</strong> {{ $item->model ?: "-" }}<br />
                            <strong>Serial Number:</strong> {{ $item->serial_number ?: "-" }}<br />
                            <strong>Category:</strong> {{ $item->category?->name ?? "-" }}
                        </div>

                        @if($item->receiptItem)
                            <div class="alert alert-info" style="padding: 10px; margin-bottom: 15px;">
                                <strong><i class="fa fa-file-text-o"></i> Purchased on:</strong>
                                <a href="{{ route("receipts.show", $item->receiptItem->receipt) }}">
                                    Receipt #{{ $item->receiptItem->receipt->id }} — {{ $item->receiptItem->receipt->name }}
                                </a>
                                from {{ $item->receiptItem->receipt->vendor ?? "unknown vendor" }}
                                on {{ $item->receiptItem->receipt->date->format("Y-m-d") }}
                                ({{ \number_format((float) $item->receiptItem->amount, 2) }} {{ $item->receiptItem->receipt->currency }})
                            </div>
                        @else
                            <div class="text-muted" style="margin-bottom: 15px;">
                                <em>No receipt linked. <a href="{{ route("inventory.edit", $item) }}">Edit item</a> to link one.</em>
                            </div>
                        @endif

                        <h2>Details</h2>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Owner</th>
                                    <th>Price</th>
                                    <th>Current Value</th>
                                    <th>Acquisition</th>
                                    <th>Date</th>
                                    <th>From</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $item->owner->label() }}</td>
                                    <td>{{ $item->price !== null ? \number_format((float) $item->price, 2) . " kr." : "-" }}</td>
                                    <td>{{ $item->current_value !== null ? \number_format((float) $item->current_value, 2) . " kr." : "-" }}</td>
                                    <td>{{ $item->acquisition_type?->label() ?? "-" }}</td>
                                    <td>{{ $item->acquisition_date?->format("Y-m-d") ?? "-" }}</td>
                                    <td>{{ $item->acquired_from ?: "-" }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <h2>Status</h2>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Changed</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $item->status->label() }}</td>
                                    <td>{{ $item->status_change_date?->format("Y-m-d") ?? "-" }}</td>
                                    <td>{{ $item->status_reason ?: "-" }}</td>
                                </tr>
                            </tbody>
                        </table>

                        @if($item->attachments->isNotEmpty())
                            <h2>Attachments</h2>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->attachments as $attachment)
                                        <tr>
                                            <td>{{ $attachment->file_name }}</td>
                                            <td>{{ $attachment->mime_type ?? "-" }}</td>
                                            <td>{{ $attachment->size !== null ? \number_format($attachment->size / 1024, 2) . " KB" : "-" }}</td>
                                            <td>
                                                <a href="{{ route("inventory.attachment", $attachment) }}" class="btn btn-info btn-sm"
                                                    style="color: #fff; padding: 4px 10px; font-size: 0.9em;" target="_blank">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        <div style="margin-top: 15px;">
                            <a href="{{ route("inventory.edit", $item) }}" class="btn btn-warning btn-sm"
                                style="color: #fff; padding: 4px 10px; font-size: 0.9em;">
                                <i class="fa fa-pencil"></i> Edit
                            </a>
                            <a href="{{ route("inventory.index") }}" class="btn btn-secondary btn-sm"
                                style="padding: 4px 10px; font-size: 0.9em;">
                                <i class="fa fa-arrow-left"></i> Back to list
                            </a>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>
