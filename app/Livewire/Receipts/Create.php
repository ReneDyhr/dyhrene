<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\CreateReceiptAction;
use App\Models\ReceiptCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;
use OpenAI\Laravel\Facades\OpenAI;

class Create extends Component
{
    use WithFileUploads;

    /**
     * @var array{name?: null|string, vendor?: null|string, description?: null|string, currency?: null|string, total?: null|float, date?: null|string, file_path?: null|string}
     */
    public array $data = [];

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
     * Extract receipt data from uploaded image using your custom OpenAI Assistant.
     */
    public function extractFromImage(): void
    {
        if (!$this->receiptImage instanceof UploadedFile) {
            \session()->flash('error', 'No image uploaded.');

            return;
        }
        $fileId = $this->uploadImageToOpenAI($this->receiptImage);

        if ($fileId === null) {
            \session()->flash('error', 'Failed to upload image to OpenAI.');

            return;
        }
        $threadId = $this->createThreadWithImage($fileId);

        if ($threadId === null) {
            \session()->flash('error', 'Failed to create thread.');

            return;
        }
        $assistantId = \Config::string('openai.assistant_id');
        $runId = $this->runAssistantAndWait($threadId, $assistantId);

        if ($runId === null) {
            \session()->flash('error', 'Failed to start or complete assistant run.');

            return;
        }
        $messages = OpenAI::threads()->messages()->list($threadId);
        $last = $messages['data'][0]['content'][0]['text']['value'] ?? null;

        if (!\is_string($last) || $last === '') {
            \session()->flash('error', 'No response from OpenAI assistant.');

            return;
        }
        $data = $this->extractJsonFromResponse($last);
        $this->mapExtractedDataToForm($data);
        \session()->flash('success', 'Receipt data extracted!');
    }

    public function save(CreateReceiptAction $action): void
    {
        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.vendor' => 'nullable|string|max:255',
            'data.description' => 'nullable|string',
            'data.currency' => 'required|string|max:10',
            'data.total' => 'required|numeric',
            'data.date' => 'required|date_format:Y-m-d\TH:i',
            'data.file_path' => 'nullable|string',
            'itemEdits.*.name' => 'required|string|max:255',
            'itemEdits.*.quantity' => 'required|integer|min:1',
            'itemEdits.*.amount' => 'required|numeric',
            'itemEdits.*.category_id' => 'required|integer|exists:receipt_categories,id',
        ]);

        $user = \Auth::user();

        if (!$user instanceof \App\Models\User) {
            \abort(403, 'Unauthorized');
        }

        // Save uploaded image to Wasabi S3 and store path in db
        if ($this->receiptImage instanceof UploadedFile) {
            $path = $this->receiptImage->store('receipts', 'wasabi');

            if ($path === false) {
                \session()->flash('error', 'Failed to upload receipt image.');

                return;
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

        \session()->flash('success', 'Receipt created!');
        $this->redirect(\route('receipts.index'));
    }

    public function render(): View
    {
        return \view('receipts.create', [
            'categories' => $this->categories,
            'itemEdits' => $this->itemEdits,
        ]);
    }

    /**
     * Uploads the image to OpenAI and returns the file id.
     */
    private function uploadImageToOpenAI(UploadedFile $image): ?string
    {
        $imageFile = \fopen($image->getRealPath(), 'rb');

        if ($imageFile === false) {
            return null;
        }
        $file = OpenAI::files()->upload([
            'purpose' => 'assistants',
            'file' => $imageFile,
        ]);

        if (\is_resource($imageFile)) {
            \fclose($imageFile);
        }

        return $file['id'] ?? null;
    }

    /**
     * Creates a thread with the image and returns the thread id.
     */
    private function createThreadWithImage(string $fileId): ?string
    {
        $thread = OpenAI::threads()->create([
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image_file', 'image_file' => ['file_id' => $fileId]],
                    ],
                ],
            ],
        ]);

        return $thread['id'] ?? null;
    }

    /**
     * Runs the assistant and waits for completion. Returns the run id or null.
     */
    private function runAssistantAndWait(string $threadId, string $assistantId): ?string
    {
        $run = OpenAI::threads()->runs()->create($threadId, [
            'assistant_id' => $assistantId,
        ]);
        $runId = $run['id'] ?? null;

        if ($runId === null || $runId === '') {
            return null;
        }
        $status = $run['status'] ?? '';
        $maxTries = 20;
        $tries = 0;

        while ($status !== 'completed' && $tries < $maxTries) {
            \sleep(2);
            $run = OpenAI::threads()->runs()->retrieve($threadId, $runId);
            $status = $run['status'] ?? '';
            $tries++;
        }

        return $status === 'completed' ? $runId : null;
    }

    /**
     * Extracts the JSON block from the assistant's response.
     *
     * @return null|array<mixed, mixed>
     */
    private function extractJsonFromResponse(string $response): ?array
    {
        if (\preg_match('/\{(?:[^{}]|(?R))*\}/s', $response, $matches) === 1) {
            $json = $matches[0];
            $data = \json_decode($json, true);

            return \is_array($data) ? $data : null;
        }

        return null;
    }

    /**
     * Maps extracted data to Livewire properties.
     *
     * @param null|array<string, mixed> $data
     */
    private function mapExtractedDataToForm(?array $data): void
    {
        if (!\is_array($data) || !isset($data['items']) || !\is_array($data['items'])) {
            \session()->flash('error', 'Could not extract items from receipt.');

            return;
        }
        // Set date and vendor
        $date = isset($data['date']) && \is_string($data['date']) ? $data['date'] : null;
        $time = isset($data['time']) && \is_string($data['time']) ? $data['time'] : null;

        if ($date !== null && $time !== null) {
            $this->data['date'] = $date . 'T' . $time;
        } elseif ($date !== null) {
            $this->data['date'] = $date;
        }

        if (isset($data['vendor']) && \is_string($data['vendor'])) {
            $this->data['vendor'] = $data['vendor'];
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
                'amount' => $amount,
                'category_id' => $categoryId,
            ];
        }
        $this->itemEdits = $itemEdits;
    }
}
