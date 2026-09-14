{{-- Flash-beskeder --}}
@if (session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

<div class="form-group">
    <label for="name">Navn</label>
    <input type="text" id="name" class="form-control" wire:model.defer="data.name" required>
</div>

<div class="form-group">
    <label for="vendor">Butik</label>
    <input type="text" id="vendor" class="form-control" wire:model.defer="data.vendor">
</div>

<div class="form-group">
    <label for="description">Beskrivelse</label>
    <textarea id="description" class="form-control" wire:model.defer="data.description"></textarea>
</div>

<div class="form-group">
    <label for="currency">Valuta</label>
    <input type="text" id="currency" class="form-control" wire:model.defer="data.currency" required maxlength="3">
</div>

<div class="form-group">
    <label for="date">Dato</label>
    <input type="datetime-local" id="date" class="form-control" wire:model.defer="data.date" required
        @if (isset($data['date']) && $data['date']) value="{{ \Illuminate\Support\Carbon::parse($data['date'])->format('Y-m-d\TH:i') }}" @endif>
</div>

<div class="form-group">
    <label for="receiptImage">Upload kvittering</label>
    <input type="file" id="receiptImage" class="form-control" wire:model="receiptImage">
    @if (!isset($this->receipt))
        <button id="extractFromImage" type="button" {{ !$this->receiptImage ? 'disabled="disabled"' : '' }}
            class="btn btn-default" style="margin-top: 8px;" wire:click="extractFromImage">Uddrag fra billede</button>
    @endif
</div>
