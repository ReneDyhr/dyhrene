<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\CreateReceiptAction;
use App\Models\ReceiptCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Create extends Component
{
    /**
     * @var ?array{name: string, vendor?: string, description?: string, currency: string, total: float, date: string, file_path?: string}
     */
    public ?array $data = null;

    /**
     * @var null|array<string, array{name: string, quantity: int, amount: float, category_id: int}>
     */
    public ?array $itemEdits = null;

    /**
     * @var null|array<int, array{id: int, name: string}>
     */
    public ?array $categories = null;

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

        if ($this->data === null) {
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
}
