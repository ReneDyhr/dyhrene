{{-- Flash Messages --}}
@if (session('success'))
    <div
        style="margin-bottom: 1rem; border-radius: 6px; background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 0.75rem 1rem;">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div
        style="margin-bottom: 1rem; border-radius: 6px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem;">
        {{ session('error') }}
    </div>
@endif

<div style="margin-bottom: 1rem;">
    <label for="name" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Name</label>
    <input type="text" id="name" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;"
        wire:model.defer="data.name" required>
</div>
<div style="margin-bottom: 1rem;">
    <label for="vendor" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Vendor</label>
    <input type="text" id="vendor" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;"
        wire:model.defer="data.vendor">
</div>
<div style="margin-bottom: 1rem;">
    <label for="description" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Description</label>
    <textarea id="description" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;"
        wire:model.defer="data.description"></textarea>
</div>
<div style="margin-bottom: 1rem;">
    <label for="currency" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Currency</label>
    <input type="text" id="currency"
        style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;"
        wire:model.defer="data.currency" required maxlength="3">
</div>
<div style="margin-bottom: 1rem;">
    <label for="date" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Date</label>
    <input type="datetime-local" id="date"
        style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;"
        wire:model.defer="data.date" required @if(isset($data['date']) && $data['date'])
        value="{{ \Illuminate\Support\Carbon::parse($data['date'])->format('Y-m-d\TH:i') }}" @endif>
</div>
<div style="margin-bottom: 1rem;">
    <label for="receiptImage" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Upload Receipt
        Image</label>
    <input type="file" id="receiptImage"
        style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;" wire:model="receiptImage">
    <button type="button" {{ !$this->receiptImage ? 'disabled="disabled"' : '' }}
        style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: #6c757d; color: #fff; border: none; border-radius: 4px; cursor: pointer;"
        wire:click="extractFromImage">Extract from Image</button>
</div>