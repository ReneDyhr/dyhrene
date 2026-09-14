@section('title', $receipt->name)
<x-layouts.app-shell :area="\App\Enums\AppArea::Household">
    <div class="view-head">
        <h1>{{ $receipt->name }}</h1>
        <p>
            {{ $receipt->vendor }} · {{ $receipt->date->locale('da')->isoFormat('D. MMMM YYYY HH:mm') }}
        </p>
    </div>

    <article class="card">
        @if ($receipt->description)
            <p style="margin: 0 0 12px;">{{ $receipt->description }}</p>
        @endif

        <div class="meta" style="margin-bottom: 14px;">
            <p style="margin: 0;">Valuta: {{ $receipt->currency }}</p>
            @if ($receipt->file_path)
                <p style="margin: 0;"><a href="{{ route('receipts.image', $receipt) }}" target="_blank">Vis kvittering</a></p>
            @else
                <p style="margin: 0;">Ingen billede</p>
            @endif
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Vare</th>
                    <th>Antal</th>
                    <th>Pris</th>
                    <th>Total</th>
                    <th>Kategori</th>
                    <th>Inventar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($receipt->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ \App\Support\Format::number((float) $item->amount) }}</td>
                        <td>{{ \App\Support\Format::number($item->total) }} {{ $receipt->currency }}</td>
                        <td>{{ $item->category?->name }}</td>
                        <td>
                            @if ($item->inventoryItem)
                                <a href="{{ route('inventory.show', $item->inventoryItem) }}">{{ $item->inventoryItem->name }}</a>
                                <button wire:click="unlinkFromInventory({{ $item->id }})"
                                    wire:confirm="Fjern kobling til inventar?" class="btn btn-xs btn-danger">Fjern</button>
                            @else
                                <select wire:change="linkToInventory({{ $item->id }}, $event.target.value)" class="form-control" style="min-width: 180px;">
                                    <option value="">-- Link --</option>
                                    @foreach ($availableItems as $inv)
                                        <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total:</td>
                    <td>{{ \App\Support\Format::number($receipt->total) }} {{ $receipt->currency }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div style="display: flex; gap: 10px; margin-top: 16px;">
            <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-primary" wire:navigate>Redigér</a>
            <a href="{{ route('receipts.index') }}" class="btn btn-default" wire:navigate>Tilbage</a>
        </div>
    </article>
</x-layouts.app-shell>
