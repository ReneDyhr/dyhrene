<div class="container mx-auto p-4 border rounded bg-gray-50">
    @if($freezerId)
        <h3 class="text-lg font-semibold mb-2">Items in Freezer</h3>
        <form wire:submit.prevent="addItem" class="flex gap-2 mb-4">
            <input type="text" wire:model.defer="name" placeholder="Item name (e.g. fries)" class="border rounded px-2 py-1" />
            <input type="number" wire:model.defer="quantity" placeholder="Qty" min="1" class="border rounded px-2 py-1 w-20" />
            <input type="text" wire:model.defer="unit" placeholder="Unit (e.g. bag, tub)" class="border rounded px-2 py-1 w-32" />
            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded">Add</button>
        </form>
        <table class="w-full text-left">
            <thead>
                <tr>
                    <th class="py-1">Item</th>
                    <th class="py-1">Quantity</th>
                    <th class="py-1">Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="py-1">{{ $item->name }}</td>
                        <td class="py-1">{{ $item->quantity }}</td>
                        <td class="py-1">{{ $item->unit }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-gray-500">Select a freezer to view or add items.</p>
    @endif
</div>
