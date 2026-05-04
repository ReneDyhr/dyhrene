<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Models\Receipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name(value: 'receipt_items_update')]
#[Description(value: 'Replace all line items on a receipt (same behavior as the web edit form: existing items are deleted, then new rows are created).')]
class UpdateReceiptItemsTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{receipt_id: int, items: list<array{name: string, quantity: int, amount: float|int, category_id: int}>} $validated */
        $validated = $request->validate([
            'receipt_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|numeric',
            'items.*.category_id' => [
                'required',
                'integer',
                Rule::exists('receipt_categories', 'id')->where('user_id', $userId),
            ],
        ]);

        $receipt = Receipt::forAuthUser()->whereKey($validated['receipt_id'])->first();

        if ($receipt === null) {
            return Response::error('Receipt not found.');
        }

        DB::transaction(function () use ($receipt, $validated): void {
            $receipt->items()->delete();

            foreach ($validated['items'] as $item) {
                $receipt->items()->create([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                    'category_id' => $item['category_id'],
                ]);
            }
        });

        $receipt->load('items');

        return Response::structured([
            'receipt_id' => $receipt->id,
            'item_count' => $receipt->items->count(),
            'total' => $receipt->total,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $itemSchema = $schema->object([
            'name' => $schema->string()->required(),
            'quantity' => $schema->integer()->required(),
            'amount' => $schema->number()->required(),
            'category_id' => $schema->integer()->required(),
        ]);

        return [
            'receipt_id' => $schema->integer()->required(),
            'items' => $schema->array()->items($itemSchema)->min(1)->description('Full new list of line items.')->required(),
        ];
    }
}
