<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Actions\CreateInventoryAttachmentAction;
use App\Actions\CreateInventoryItemAction;
use App\Enums\InventoryAcquisitionTypeEnum;
use App\Enums\InventoryOwnerEnum;
use App\Enums\InventoryStatusEnum;
use App\Models\InventoryCategory;
use App\Models\ReceiptItem;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $name = '';

    public ?int $category_id = null;

    public string $brand = '';

    public string $model = '';

    public string $serial_number = '';

    public string $owner = 'shared';

    public ?string $price = null;

    public ?string $current_value = null;

    public string $acquisition_type = '';

    public ?string $acquisition_date = null;

    public string $acquired_from = '';

    public string $status = 'owned';

    public ?string $status_change_date = null;

    public string $status_reason = '';

    /** @var null|\Illuminate\Http\UploadedFile */
    public $upload;

    public ?int $receipt_item_id = null;

    /** @var Collection<int, ReceiptItem> */
    public Collection $availableReceiptItems;

    /** @var array<int, InventoryCategory> */
    protected array $categoryList = [];

    public function mount(): void
    {
        $this->categoryList = InventoryCategory::query()->forAuthUser()->orderBy('name')->get()->all();
        $this->availableReceiptItems = ReceiptItem::query()
            ->whereNull('inventory_item_id')
            ->whereHas('receipt', fn(Builder $q) => $q->where('user_id', \Auth::id()))
            ->with('receipt')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    public function render(): View
    {
        return \view('livewire.inventory.create', [
            'title' => 'Create Item',
            'categories' => $this->categoryList,
            'owners' => InventoryOwnerEnum::cases(),
            'acquisitionTypes' => InventoryAcquisitionTypeEnum::cases(),
            'statuses' => InventoryStatusEnum::cases(),
        ]);
    }

    public function linkReceipt(): void
    {
        if ($this->receipt_item_id === null) {
            return;
        }
        $receiptItem = ReceiptItem::query()
            ->whereHas('receipt', fn(Builder $q) => $q->where('user_id', \Auth::id()))
            ->with('receipt')
            ->findOrFail($this->receipt_item_id);

        // Only auto-fill if fields are empty
        if ($this->price === null || $this->price === '') {
            $this->price = $receiptItem->amount;
        }

        if ($this->acquisition_date === null || $this->acquisition_date === '') {
            if ($receiptItem->receipt !== null) {
                $this->acquisition_date = $receiptItem->receipt->date->format('Y-m-d');
            }
        }

        if ($this->acquired_from === '') {
            $this->acquired_from = $receiptItem->receipt->vendor ?? '';
        }

        \session()->flash('success', 'Fields populated from receipt.');
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category_id' => ['nullable', 'integer', Rule::exists('inventory_categories', 'id')->where(fn(QueryBuilder $q) => $q->where('user_id', \Auth::id()))],
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'owner' => 'required|string|in:' . \implode(',', \array_column(InventoryOwnerEnum::cases(), 'value')),
            'price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'acquisition_type' => 'nullable|string|in:' . \implode(',', \array_column(InventoryAcquisitionTypeEnum::cases(), 'value')),
            'acquisition_date' => 'nullable|date',
            'acquired_from' => 'nullable|string|max:255',
            'status' => 'required|string|in:' . \implode(',', \array_column(InventoryStatusEnum::cases(), 'value')),
            'status_change_date' => 'nullable|date',
            'status_reason' => 'nullable|string|max:255',
            'upload' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,webp',
        ]);

        $user = \Auth::user();

        if (!$user instanceof User) {
            \abort(401);
        }

        $data = [
            'name' => $this->name,
            'category_id' => $this->category_id,
            'brand' => $this->brand !== '' ? $this->brand : null,
            'model' => $this->model !== '' ? $this->model : null,
            'serial_number' => $this->serial_number !== '' ? $this->serial_number : null,
            'owner' => $this->owner,
            'price' => $this->price !== '' && $this->price !== null ? $this->price : null,
            'current_value' => $this->current_value !== '' && $this->current_value !== null ? $this->current_value : null,
            'acquisition_type' => $this->acquisition_type !== '' ? $this->acquisition_type : null,
            'acquisition_date' => $this->acquisition_date !== '' && $this->acquisition_date !== null ? $this->acquisition_date : null,
            'acquired_from' => $this->acquired_from !== '' ? $this->acquired_from : null,
            'status' => $this->status,
            'status_change_date' => $this->status_change_date !== '' && $this->status_change_date !== null ? $this->status_change_date : null,
            'status_reason' => $this->status_reason !== '' ? $this->status_reason : null,
        ];

        $item = (new CreateInventoryItemAction())->handle($user, $data);

        if ($this->upload !== null) {
            (new CreateInventoryAttachmentAction())->handle($item, $this->upload);
        }

        if ($this->receipt_item_id !== null) {
            $receiptItem = ReceiptItem::query()
                ->whereHas('receipt', fn(Builder $q) => $q->where('user_id', \Auth::id()))
                ->findOrFail($this->receipt_item_id);
            $receiptItem->update(['inventory_item_id' => $item->id]);
        }

        \session()->flash('success', 'Item created!');

        $this->redirect(\route('inventory.show', $item));
    }
}
