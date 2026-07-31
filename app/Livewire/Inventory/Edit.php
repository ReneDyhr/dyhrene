<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Actions\CreateInventoryAttachmentAction;
use App\Actions\DeleteInventoryAttachmentAction;
use App\Actions\UpdateInventoryItemAction;
use App\Enums\InventoryAcquisitionTypeEnum;
use App\Enums\InventoryOwnerEnum;
use App\Enums\InventoryStatusEnum;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public InventoryItem $item;

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

    /** @var array<int, InventoryCategory> */
    protected array $categoryList = [];

    public function mount(InventoryItem $item): void
    {
        \abort_unless($item->user_id === \Auth::id(), 403);

        $this->item = $item->load(['category', 'attachments']);
        $this->categoryList = InventoryCategory::query()->forAuthUser()->orderBy('name')->get()->all();

        $this->name = $this->item->name;
        $this->category_id = $this->item->category_id;
        $this->brand = $this->item->brand ?? '';
        $this->model = $this->item->model ?? '';
        $this->serial_number = $this->item->serial_number ?? '';
        $this->owner = $this->item->owner->value;
        $this->price = $this->item->price;
        $this->current_value = $this->item->current_value;
        $this->acquisition_type = $this->item->acquisition_type !== null ? $this->item->acquisition_type->value : '';
        $this->acquisition_date = $this->item->acquisition_date?->format('Y-m-d');
        $this->acquired_from = $this->item->acquired_from ?? '';
        $this->status = $this->item->status->value;
        $this->status_change_date = $this->item->status_change_date?->format('Y-m-d');
        $this->status_reason = $this->item->status_reason ?? '';
    }

    public function render(): View
    {
        return \view('livewire.inventory.edit', [
            'title' => 'Edit Item',
            'categories' => $this->categoryList,
            'owners' => InventoryOwnerEnum::cases(),
            'acquisitionTypes' => InventoryAcquisitionTypeEnum::cases(),
            'statuses' => InventoryStatusEnum::cases(),
        ]);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|integer|exists:inventory_categories,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'owner' => 'required|string|in:shared,rene,jeanette',
            'price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'acquisition_type' => 'nullable|string|in:bought,gift,inherited,found,built',
            'acquisition_date' => 'nullable|date',
            'acquired_from' => 'nullable|string|max:255',
            'status' => 'required|string|in:owned,sold,stolen,lost,donated,lent_out,in_repair',
            'status_change_date' => 'nullable|date',
            'status_reason' => 'nullable|string|max:255',
            'upload' => 'nullable|file|max:10240',
        ]);

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

        (new UpdateInventoryItemAction())->handle($this->item, $data);

        if ($this->upload !== null) {
            (new CreateInventoryAttachmentAction())->handle($this->item, $this->upload);
        }

        \session()->flash('success', 'Item updated!');

        $this->redirect(\route('inventory.show', $this->item));
    }

    public function removeAttachment(int $attachmentId): void
    {
        $attachment = $this->item->attachments()->findOrFail($attachmentId);

        (new DeleteInventoryAttachmentAction())->handle($attachment);
    }
}
