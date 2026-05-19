<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\CreateReceiptAction;
use App\Models\Receipt;
use App\Models\ReceiptCategory;
use App\Models\User;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;
use App\Services\Receipts\N8nReceiptExtractor;
use App\Services\Receipts\ReceiptExtractedDataMapper;
use App\Support\ReceiptDuplicateGuard;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    /**
     * @var array{name?: null|string, vendor?: null|string, description?: null|string, currency?: null|string, date?: null|string, file_path?: null|string}
     */
    public array $data = [
        'currency' => 'kr.',
    ];

    /**
     * @var null|array<string, array{name: string, quantity: int, amount: float, category_id: int}>
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

    public function mount(): void
    {
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
        $this->itemEdits = null;
    }

    public function addItem(): void
    {
        $defaultCategoryId = isset($this->categories[0], $this->categories[0]['id'])
            ? $this->categories[0]['id']
            : 0;
        $id = \uniqid('new_', false);
        $this->itemEdits[$id] = [
            'name' => '',
            'quantity' => 1,
            'amount' => 0,
            'category_id' => $defaultCategoryId,
        ];
    }

    public function deleteItem(string $id): void
    {
        unset($this->itemEdits[$id]);
    }

    /**
     * Update the order of receipt items after drag-and-drop.
     *
     * @param array<int, string> $orderedIds
     */
    public function updateItemOrder(array $orderedIds): void
    {
        if ($this->itemEdits === null) {
            return;
        }
        $newOrder = [];

        foreach ($orderedIds as $id) {
            if (isset($this->itemEdits[$id])) {
                $newOrder[$id] = $this->itemEdits[$id];
            }
        }
        $this->itemEdits = $newOrder;
    }

    /**
     * Extract receipt data from uploaded image using n8n webhook.
     */
    public function extractFromImage(N8nReceiptExtractor $extractor, ReceiptExtractedDataMapper $mapper): void
    {
        if (!$this->receiptImage instanceof UploadedFile) {
            Session::flash('error', 'No image uploaded.');

            return;
        }

        $user = \Auth::user();

        if (!$user instanceof User) {
            Session::flash('error', 'Unauthorized.');

            return;
        }

        $image = PdfConverter::convertToJpg($this->receiptImage);
        $imagePath = $image->getRealPath();
        $isTemporaryFile = $image instanceof \Illuminate\Http\File;
        $filename = $image instanceof UploadedFile ? $image->getClientOriginalName() : 'receipt.jpg';
        $fileContents = false;

        if ($image instanceof UploadedFile) {
            $fileContents = $image->get();

            if ($fileContents === false || $fileContents === '') {
                $pathname = $image->getPathname();

                if (Storage::disk('wasabi')->exists($pathname)) {
                    $storageContents = Storage::disk('wasabi')->get($pathname);

                    if (!empty($storageContents)) {
                        $fileContents = $storageContents;
                    }
                }
            }
        } elseif ($imagePath !== false && \file_exists($imagePath)) {
            $fileContents = \file_get_contents($imagePath);
        }

        if ($fileContents === false || $fileContents === '') {
            if ($isTemporaryFile && \is_string($imagePath) && \file_exists($imagePath)) {
                @\unlink($imagePath);
            }
            Session::flash('error', 'Failed to read image file.');

            return;
        }

        try {
            $output = $extractor->extract($fileContents, $filename);
            $mapped = $mapper->map(
                $user,
                $output,
                defaultCurrency: $this->data['currency'] ?? 'kr.',
            );
            $this->applyMappedDataToForm($mapped);
            Session::flash('success', 'Receipt data extracted!');
        } catch (ReceiptExtractionException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Error calling webhook: ' . $e->getMessage());
        } finally {
            if ($isTemporaryFile && \is_string($imagePath) && \file_exists($imagePath)) {
                @\unlink($imagePath);
            }
        }
    }

    public function save(CreateReceiptAction $action): void
    {
        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.vendor' => 'nullable|string|max:255',
            'data.description' => 'nullable|string',
            'data.currency' => 'required|string|max:10',
            'data.date' => 'required|date_format:Y-m-d\TH:i',
            'itemEdits.*.name' => 'required|string|max:255',
            'itemEdits.*.quantity' => 'required|integer|not_in:0',
            'itemEdits.*.amount' => 'required|numeric',
            'itemEdits.*.category_id' => 'required|integer|exists:receipt_categories,id',
        ]);

        $user = \Auth::user();

        if (!$user instanceof User) {
            \abort(403, 'Unauthorized');
        }

        // Calculate total from items
        $total = 0.0;

        if ($this->itemEdits !== null) {
            foreach ($this->itemEdits as $item) {
                $total += $item['amount'] * $item['quantity'];
            }
        }
        $vendor = $this->data['vendor'] ?? null;

        try {
            $receiptDate = Carbon::parse($this->data['date'] ?? '');
        } catch (\Throwable) {
            Session::flash('error', 'Invalid receipt date.');

            return;
        }

        if (ReceiptDuplicateGuard::duplicateExists($user, $vendor, $receiptDate, $total)) {
            Session::flash('error', 'This receipt has already been uploaded. A receipt with the same vendor, time, and total price already exists.');

            return;
        }

        // Save uploaded image to Wasabi S3 and store path in db
        if ($this->receiptImage instanceof UploadedFile) {
            $imageForSave = PdfConverter::convertToJpg($this->receiptImage);

            if ($imageForSave instanceof UploadedFile) {
                $path = $imageForSave->store('receipts', 'wasabi');
            } else {
                $path = \Storage::disk('wasabi')->putFile('receipts', $imageForSave);
            }

            if ($path === false || $path === '') {
                Session::flash('error', 'Failed to save receipt image.');

                return;
            }

            if ($imageForSave instanceof \Illuminate\Http\File && \file_exists($imageForSave->getPathname())) {
                @\unlink($imageForSave->getPathname());
            }

            $this->data['file_path'] = $path;
        }

        if ($this->data === []) {
            throw new \RuntimeException('Receipt data is missing.');
        }

        $receipt = $action->handle($user, $this->data);

        if ($this->itemEdits !== null) {
            foreach ($this->itemEdits as $item) {
                $receipt->items()->create([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                    'category_id' => $item['category_id'],
                ]);
            }
        }

        Session::flash('success', 'Receipt created!');
        $this->redirect(\route('receipts.show', ['receipt' => $receipt->id]));
    }

    public function render(): View
    {
        return \view('receipts.create', [
            'categories' => $this->categories,
            'itemEdits' => $this->itemEdits,
        ]);
    }

    /**
     * Public method to recalculate the total for edit view.
     */
    public function calculateTotal(): void
    {
        // No-op: total is calculated in the Blade for now, but this allows wire:change to work without error.
    }

    private function applyMappedDataToForm(\App\Services\Receipts\DTOs\MappedReceiptData $mapped): void
    {
        $this->data['name'] = $mapped->header['name'];
        $this->data['vendor'] = $mapped->header['vendor'] ?? null;
        $this->data['currency'] = $mapped->header['currency'];
        $this->data['date'] = $mapped->header['date'];

        if (isset($mapped->header['description'])) {
            $this->data['description'] = $mapped->header['description'];
        }

        $itemEdits = [];

        foreach ($mapped->items as $item) {
            $itemEdits[\uniqid('ai_', false)] = $item;
        }

        $this->itemEdits = $itemEdits;
    }
}
