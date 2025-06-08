<div>
    @section('title', 'Receipts')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <a href="{{ route('receipts.create') }}" class="btn btn-success mb-3" style="color: #fff;">
                            <i class="fa fa-plus"></i> New Receipt
                        </a>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Vendor</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipts as $receipt)
                                    <tr>
                                        <td>{{ $receipt->name }}</td>
                                        <td>{{ $receipt->vendor }}</td>
                                        <td>{{ $receipt->total }} {{ $receipt->currency }}</td>
                                        <td>{{ $receipt->date->format('F j, Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-info btn-sm"
                                                style="color: #fff;">View</a>
                                            <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-warning btn-sm"
                                                style="color: #fff;">Edit</a>
                                            <button wire:confirm="Are you sure?"
                                                wire:click="deleteReceipt({{ $receipt->id }})" class="btn btn-danger btn-sm"
                                                style="color: #fff;">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>