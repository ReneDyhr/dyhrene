<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'receipt_get_items')]
#[Description(value: 'Fetch line items for a receipt by id (must belong to the authenticated user).')]
#[IsReadOnly(value: true)]
class GetReceiptItemsTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{receipt_id: int} $validated */
        $validated = $request->validate([
            'receipt_id' => 'required|integer',
        ]);

        $receipt = Receipt::forAuthUser()->whereKey($validated['receipt_id'])->first();

        if ($receipt === null) {
            return Response::error('Receipt not found.');
        }

        $items = $receipt->items()->with('category')->orderBy('id')->get();

        return Response::structured([
            'receipt_id' => $receipt->id,
            'items' => $items->map(static function (ReceiptItem $item): array {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'amount' => $item->amount,
                    'category_id' => $item->category_id,
                    'category_name' => $item->category?->name,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'receipt_id' => $schema->integer()->description('Receipt id.')->required(),
        ];
    }
}
