<div class="form-group">
    <label>Name</label>
    <input type="text" wire:model="name" class="form-control" required>
    @error("name") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Category</label>
    <select wire:model="category_id" class="form-control">
        <option value="">-- Select Category --</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
    </select>
    @error("category_id") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Brand</label>
    <input type="text" wire:model="brand" class="form-control">
    @error("brand") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Model</label>
    <input type="text" wire:model="model" class="form-control">
    @error("model") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Serial Number</label>
    <input type="text" wire:model="serial_number" class="form-control">
    @error("serial_number") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Owner</label>
    <select wire:model="owner" class="form-control">
        @foreach($owners as $ownerEnum)
            <option value="{{ $ownerEnum->value }}">{{ $ownerEnum->label() }}</option>
        @endforeach
    </select>
    @error("owner") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="col-6">
        <div class="form-group">
            <label>Price</label>
            <input type="number" step="0.01" wire:model="price" class="form-control">
            @error("price") <span class="error">{{ $message }}</span> @enderror
        </div>
    </div>
    <div class="col-6">
        <div class="form-group">
            <label>Current Value</label>
            <input type="number" step="0.01" wire:model="current_value" class="form-control">
            @error("current_value") <span class="error">{{ $message }}</span> @enderror
        </div>
    </div>
    <div class="clear"></div>

<div class="form-group">
    <label>Acquisition Type</label>
    <select wire:model="acquisition_type" class="form-control">
        <option value="">-- Select --</option>
        @foreach($acquisitionTypes as $type)
            <option value="{{ $type->value }}">{{ $type->label() }}</option>
        @endforeach
    </select>
    @error("acquisition_type") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Acquisition Date</label>
    <input type="date" wire:model="acquisition_date" class="form-control">
    @error("acquisition_date") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Acquired From</label>
    <input type="text" wire:model="acquired_from" class="form-control">
    @error("acquired_from") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Link to Receipt</label>
    <div style="display: flex; gap: 8px;" wire:ignore>
        <select id="receipt-item-select" wire:model="receipt_item_id" class="form-control" style="flex: 1;">
            <option value="">-- Select receipt item --</option>
            @foreach($availableReceiptItems as $ri)
                <option value="{{ $ri->id }}">
                    {{ $ri->receipt->vendor ?? "Receipt" }} — {{ $ri->name }} ({{ \number_format((float) $ri->amount, 2) }})
                </option>
            @endforeach
        </select>
        <button type="button" wire:click="linkReceipt" class="btn btn-info btn-sm"
                style="white-space: nowrap;">
            <i class="fa fa-magic"></i> Auto-fill
        </button>
    </div>
    @error("receipt_item_id") <span class="error">{{ $message }}</span> @enderror
</div>

@script
<script>
let tsReceiptSelect = null;

function initReceiptSelect() {
    const el = document.getElementById('receipt-item-select');
    if (!el) return;

    if (tsReceiptSelect) {
        tsReceiptSelect.destroy();
    }

    const selectedValue = el.value;
    const options = Array.from(el.options).map(opt => ({
        value: opt.value,
        text: opt.textContent
    }));

    tsReceiptSelect = new window.TomSelect(el, {
        options: options,
        placeholder: 'Search receipt items...',
        allowEmptyOption: true,
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        onChange: function(value) {
            @this.set('receipt_item_id', value === '' ? null : parseInt(value));
        }
    });

    if (selectedValue) {
        tsReceiptSelect.setValue(selectedValue);
    }
}

initReceiptSelect();
document.addEventListener('livewire:update', function() {
    setTimeout(initReceiptSelect, 50);
});
</script>
@endscript

<div class="form-group">
    <label>Status</label>
    <select wire:model="status" class="form-control">
        @foreach($statuses as $statusEnum)
            <option value="{{ $statusEnum->value }}">{{ $statusEnum->label() }}</option>
        @endforeach
    </select>
    @error("status") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Status Change Date</label>
    <input type="date" wire:model="status_change_date" class="form-control">
    @error("status_change_date") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Status Reason</label>
    <input type="text" wire:model="status_reason" class="form-control">
    @error("status_reason") <span class="error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label>Attachment</label>
    <input type="file" wire:model="upload" class="form-control">
    <small class="text-muted">Max 10MB. Receipts, photos, documents.</small>
    @error("upload") <span class="error">{{ $message }}</span> @enderror
</div>
