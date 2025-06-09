<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\UpdateReceiptAction;
use App\Models\Receipt;
use App\Models\ReceiptCategory;
use App\Models\ReceiptItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Receipt $receipt;

    /**
     * @var ?array{name?: string, vendor?: string, description?: string, currency?: string, date?: string, file_path?: string}
     */
    public ?array $data = null;

    /**
     * @var null|array<int, array{id: int, name: string, quantity: int, amount: float, category_id: int}>
     */
    public ?array $items = null;

    /**
     * @var null|array<int, array{name: string, quantity: int, amount: float, category_id: int}>
     */
    public ?array $itemEdits = null;

    /**
     * @var null|array<int, array{id: int, name: string}>
     */
    public ?array $categories = null;

    /**
     * @var null|UploadedFile
     */
    public $receiptImage;

    public function mount(Receipt $receipt): void
    {
        $this->receipt = $receipt;
        // @phpstan-ignore assign.propertyType
        $this->data = $receipt->toArray();

        if (\is_string($this->data['date'])) {
            $this->data['date'] = \Carbon\Carbon::parse($this->data['date'])->format('Y-m-d H:i:s');
        }

        $this->categories = \array_map(
            // @phpstan-ignore argument.type
            static fn(array $cat): array => [
                'id' => isset($cat['id']) ? (int) $cat['id'] : 0,
                'name' => isset($cat['name']) ? (string) $cat['name'] : '',
            ],
            ReceiptCategory::query()
                ->where('user_id', \Auth::id())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray(),
        );
        // @phpstan-ignore assign.propertyType
        $this->items = $receipt->items()->with('category')->get()->map(function (ReceiptItem $item): array {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'amount' => $item->amount,
                'category_id' => $item->category_id,
            ];
        })->toArray();
        // @phpstan-ignore assign.propertyType
        $this->itemEdits = \collect($this->items)->keyBy('id')->toArray();
    }

    public function save(UpdateReceiptAction $action): void
    {
        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.vendor' => 'nullable|string|max:255',
            'data.description' => 'nullable|string',
            'data.currency' => 'required|string|max:10',
            'data.date' => 'required|date',
            'data.file_path' => 'nullable|string',
            'itemEdits.*.name' => 'required|string|max:255',
            'itemEdits.*.quantity' => 'required|integer|min:1',
            'itemEdits.*.amount' => 'required|numeric',
            'itemEdits.*.category_id' => 'required|integer|exists:receipt_categories,id',
        ]);

        if ($this->data === null) {
            throw new \LogicException('Receipt data must not be null.');
        }

        // Save uploaded image to Wasabi S3 and store path in db
        if ($this->receiptImage instanceof UploadedFile) {
            $imageForSave = PdfConverter::convertToJpg($this->receiptImage);

            if ($imageForSave instanceof UploadedFile) {
                $path = $imageForSave->store('receipts', 'wasabi');
            } else {
                $path = \Storage::disk('wasabi')->putFile('receipts', $imageForSave);
                $path = false;
            }

            if ($imageForSave instanceof \Illuminate\Http\File && \file_exists($imageForSave->getPathname())) {
                @\unlink($imageForSave->getPathname());
            }

            if ($path === false) {
                \Session::flash('error', 'Failed to upload receipt image.');

                return;
            }
            $this->data['file_path'] = $path;
        }

        $action->handle($this->receipt, $this->data);

        // Save items
        if ($this->itemEdits === null) {
            throw new \LogicException('Item edits must not be null.');
        }

        foreach ($this->itemEdits as $id => $item) {
            $receiptItem = $this->receipt->items()->find($id);

            if ($receiptItem !== null) {
                $receiptItem->update([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                    'category_id' => $item['category_id'],
                ]);
            }
        }
        \session()->flash('success', 'Receipt and items updated!');
        $this->redirect(\route('receipts.index'));
    }

    public function addItem(): void
    {
        $defaultCategoryId = $this->categories[0]['id'] ?? null;
        $new = $this->receipt->items()->create([
            'name' => '',
            'quantity' => 1,
            'amount' => 0,
            'category_id' => $defaultCategoryId ?? 0,
        ]);
        $this->itemEdits[$new->id] = [
            'name' => '',
            'quantity' => 1,
            'amount' => 0.0,
            'category_id' => $defaultCategoryId ?? 0,
        ];
        $this->mount($this->receipt->refresh());
    }

    public function deleteItem(int $itemId): void
    {
        $this->receipt->items()->where('id', $itemId)->delete();
        unset($this->itemEdits[$itemId]);
        $this->mount($this->receipt->refresh());
    }

    /**
     * Public method to recalculate the total for edit view.
     */
    public function calculateTotal(): void
    {
        // No-op: total is calculated in the Blade for now, but this allows wire:change to work without error.
    }

    public function render(): View
    {
        return \view('receipts.edit', [
            'receipt' => $this->receipt,
        ]);
    }
}
