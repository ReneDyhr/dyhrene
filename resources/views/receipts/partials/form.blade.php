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
    <label for="total" class="form-label">Total</label>
    <input type="number" step="0.01" id="total" class="form-control" wire:model.defer="data.total" required>
</div>
<div class="mb-3">
    <label for="date" class="form-label">Date</label>
    <input type="date" id="date" class="form-control" wire:model.defer="data.date" required>
</div>
<div class="mb-3">
    <label for="file_path" class="form-label">File Path</label>
    <input type="text" id="file_path" class="form-control" wire:model.defer="data.file_path">
</div>