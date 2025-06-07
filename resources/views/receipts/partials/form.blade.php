{{-- Flash Messages --}}
@if (session('success'))
    <div class="mb-4 rounded bg-green-100 border border-green-300 text-green-800 px-4 py-3">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded bg-red-100 border border-red-300 text-red-800 px-4 py-3">
        {{ session('error') }}
    </div>
@endif

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" class="form-control" wire:model.defer="data.name" required>
</div>
<div class="mb-3">
    <label for="vendor" class="form-label">Vendor</label>
    <input type="text" id="vendor" class="form-control" wire:model.defer="data.vendor">
</div>
<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea id="description" class="form-control" wire:model.defer="data.description"></textarea>
</div>
<div class="mb-3">
    <label for="currency" class="form-label">Currency</label>
    <input type="text" id="currency" class="form-control" wire:model.defer="data.currency" required maxlength="3">
</div>
<div class="mb-3">
    <label for="date" class="form-label">Date</label>
    <input type="datetime-local" id="date" class="form-control" wire:model.defer="data.date" required>
</div>
<div class="mb-3">
    <label for="file_path" class="form-label">File Path</label>
    <input type="text" id="file_path" class="form-control" wire:model.defer="data.file_path">
</div>
<div class="mb-3">
    <label for="receiptImage" class="form-label">Upload Receipt Image</label>
    <input type="file" id="receiptImage" class="form-control" wire:model="receiptImage" accept="image/*">
    <button type="button" class="btn btn-secondary mt-2" wire:click="extractFromImage">Extract from Image</button>
</div>