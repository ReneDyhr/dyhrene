<div>
    @section('title', $receipt->name)
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>{{ $receipt->name }}</h1>
                        <p><strong>Vendor:</strong> {{ $receipt->vendor }}</p>
                        <p><strong>Total:</strong> {{ $receipt->total }}</p>
                        <p><strong>Date:</strong> {{ $receipt->date }}</p>
                        <p><strong>Description:</strong> {{ $receipt->description }}</p>
                        <p><strong>Currency:</strong> {{ $receipt->currency }}</p>
                        <p><strong>File:</strong> {{ $receipt->file_path }}</p>
                        <h2>Items</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipt->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->amount }}</td>
                                        <td>{{ $item->category?->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Back to list</a>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>