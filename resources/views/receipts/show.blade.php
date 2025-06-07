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
                        <div class="description">
                            <strong>Vendor:</strong> {{ $receipt->vendor }}<br />
                            <strong>Date:</strong> {{ $receipt->date->format('F j, Y H:i') }}<br />
                            <strong>Description:</strong> {{ $receipt->description }}<br />
                            <strong>Currency:</strong> {{ $receipt->currency }}<br />
                            <strong>File:</strong>
                            @if($receipt->file_path)
                                <a href="#"
                                    onclick="event.preventDefault(); document.getElementById('image-modal').style.display = 'flex';"
                                    style="color: #53875F; font-size: 14px;">View Receipt</a>
                                <!-- Modal -->
                                <div id="image-modal"
                                    style="display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(0,0,0,0.6);">
                                    <div
                                        style="background: #fff; border-radius: 8px; box-shadow: 0 2px 16px rgba(0,0,0,0.2); padding: 1.5rem; max-width: 600px; width: 100%; position: relative;">
                                        <button onclick="document.getElementById('image-modal').style.display = 'none';"
                                            style="position: absolute; top: 12px; right: 12px; background: none; border: none; cursor: pointer; color: #888; font-size: 1.5rem;">
                                            &times;
                                        </button>
                                        <img src="{{ route('receipts.image', $receipt) }}" alt="Receipt Image"
                                            style="max-width: 100%; max-height: 70vh; display: block; margin: 0 auto; border-radius: 4px;">
                                    </div>
                                </div>
                            @else
                                <span style="color: #888;">No image</span>
                            @endif
                        </div>
                        <h2>Items</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Total</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipt->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->amount }}</td>
                                        <td>{{ $item->total }}</td>
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