<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\CreateReceiptAction;
use App\Models\Receipt;
use App\Models\ReceiptCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
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
    public function extractFromImage(): void
    {
        if (!$this->receiptImage instanceof UploadedFile) {
            Session::flash('error', 'No image uploaded.');

            return;
        }

        $webhookUrl = \config('n8n.webhook_url');

        if (!\is_string($webhookUrl) || \trim($webhookUrl) === '') {
            Session::flash('error', 'n8n webhook URL is not configured.');

            return;
        }

        $image = PdfConverter::convertToJpg($this->receiptImage);
        $imagePath = $image->getRealPath();

        if ($imagePath === false || !\file_exists($imagePath)) {
            Session::flash('error', 'Failed to process image.');

            return;
        }

        $mimeType = $image instanceof UploadedFile ? $image->getMimeType() : 'image/jpeg';
        $isTemporaryFile = $image instanceof \Illuminate\Http\File;
        $filename = $image instanceof UploadedFile ? $image->getClientOriginalName() : 'receipt.jpg';

        // Read file contents for multipart upload
        $fileContents = \file_get_contents($imagePath);

        if ($fileContents === false) {
            Session::flash('error', 'Failed to read image file.');

            return;
        }

        /** @var string $fileContents */
        try {
            $response = Http::timeout(120)
                ->attach('File', $fileContents, $filename)
                ->post($webhookUrl);

            // Clean up temporary file if it was created by PdfConverter
            if ($isTemporaryFile) {
                @\unlink($imagePath);
            }

            if (!$response->successful()) {
                Session::flash('error', 'Failed to extract receipt data from webhook.');

                return;
            }

            $responseData = $response->json();

            if (!\is_array($responseData)) {
                Session::flash('error', 'Invalid response from webhook.');

                return;
            }

            // Extract data from the "output" wrapper
            $outputData = $responseData['output'] ?? null;

            if (!\is_array($outputData)) {
                Session::flash('error', 'No output data in webhook response.');

                return;
            }

            /** @var array<string, mixed> $outputData */
            $this->mapExtractedDataToForm($outputData);
            Session::flash('success', 'Receipt data extracted!');
        } catch (\Throwable $e) {
            // Clean up temporary file in case of error
            if ($isTemporaryFile && \file_exists($imagePath)) {
                @\unlink($imagePath);
            }
            Session::flash('error', 'Error calling webhook: ' . $e->getMessage());
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

        if (!$user instanceof \App\Models\User) {
            \abort(403, 'Unauthorized');
        }

        // Calculate total from items
        $total = 0.0;

        if ($this->itemEdits !== null) {
            foreach ($this->itemEdits as $item) {
                $total += $item['amount'] * $item['quantity'];
            }
        }
        // Check for duplicate receipt (same vendor, date/time, and total)
        $vendor = $this->data['vendor'] ?? null;
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i', $this->data['date'] ?? '');

        if ($dateTime !== false) {
            $existingReceipts = Receipt::query()
                ->where('user_id', $user->id)
                ->where('date', $dateTime->format('Y-m-d H:i:s'))
                ->where(function (Builder $query) use ($vendor): void {
                    if ($vendor === null) {
                        $query->whereNull('vendor');
                    } else {
                        $query->where('vendor', $vendor);
                    }
                })
                ->with('items')
                ->get();

            foreach ($existingReceipts as $existingReceipt) {
                $existingTotal = 0.0;

                foreach ($existingReceipt->items as $item) {
                    $existingTotal += $item->amount * $item->quantity;
                }

                // Compare totals with a small tolerance for floating point precision
                if (\abs($total - $existingTotal) < 0.01) {
                    Session::flash('error', 'This receipt has already been uploaded. A receipt with the same vendor, time, and total price already exists.');

                    return;
                }
            }
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

    /**
     * Maps extracted data to Livewire properties.
     *
     * @param null|array<string, mixed> $data
     */
    private function mapExtractedDataToForm(?array $data): void
    {
        if (!\is_array($data) || !isset($data['items']) || !\is_array($data['items'])) {
            Session::flash('error', 'Could not extract items from receipt.');

            return;
        }
        // Set date and vendor
        $date = isset($data['date']) && \is_string($data['date']) ? $data['date'] : null;
        $time = isset($data['time']) && \is_string($data['time']) ? $data['time'] : null;

        if ($date !== null && $time !== null) {
            $this->data['date'] = $date . 'T' . \substr($time, 0, 5);
        } elseif ($date !== null) {
            $this->data['date'] = $date;
        }

        if (isset($data['vendor']) && \is_string($data['vendor'])) {
            $this->data['vendor'] = $data['vendor'];
            $this->data['name'] = $data['vendor'];
        }
        // Map categories to IDs
        $categoryMap = \collect($this->categories)->mapWithKeys(fn(array $cat): array => [\strtolower($cat['name']) => $cat['id']]);
        $itemEdits = [];

        foreach ($data['items'] as $itemRaw) {
            if (!\is_array($itemRaw)) {
                continue;
            }
            $name = isset($itemRaw['description']) && \is_string($itemRaw['description']) ? $itemRaw['description'] : '';
            $quantity = isset($itemRaw['quantity']) && \is_int($itemRaw['quantity']) ? $itemRaw['quantity'] : 1;
            $amount = isset($itemRaw['price']) && (\is_float($itemRaw['price']) || \is_int($itemRaw['price'])) ? (float) $itemRaw['price'] : 0.0;
            $catName = isset($itemRaw['category']) && \is_string($itemRaw['category']) ? \strtolower($itemRaw['category']) : '';
            $categoryId = $categoryMap[$catName] ?? ($this->categories[0]['id'] ?? 0);
            $itemEdits[\uniqid('ai_', false)] = [
                'name' => $name,
                'quantity' => $quantity,
                'amount' => $amount / $quantity,
                'category_id' => $categoryId,
            ];
        }
        $this->itemEdits = $itemEdits;
    }
}
