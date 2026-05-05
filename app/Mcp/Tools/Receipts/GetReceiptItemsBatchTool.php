<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Mcp\Receipts\ReceiptMcpItemPayload;
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

#[Name(value: 'receipt_get_items_batch')]
#[Description(value: 'Fetch line items for one or many receipts in a single call (for statistics). Only receipts owned by the user are returned; unknown ids are listed separately.')]
#[IsReadOnly(value: true)]
class GetReceiptItemsBatchTool extends Tool
{
    private const MAX_RECEIPTS = 200;

    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{receipt_ids: list<int>} $validated */
        $validated = $request->validate([
            'receipt_ids' => 'required|array|min:1|max:' . self::MAX_RECEIPTS,
            'receipt_ids.*' => 'integer|distinct',
        ]);

        $requested = \array_values(\array_unique($validated['receipt_ids']));

        $validIdsFromDb = [];
        $validSet = [];

        foreach (Receipt::forAuthUser()->whereIn('id', $requested)->pluck('id') as $id) {
            if (\is_int($id)) {
                $validIdsFromDb[] = $id;
                $validSet[$id] = true;
            } elseif (\is_string($id) && \ctype_digit($id)) {
                $intId = (int) $id;
                $validIdsFromDb[] = $intId;
                $validSet[$intId] = true;
            }
        }
        $missingReceiptIds = [];

        foreach ($requested as $rid) {
            if (!isset($validSet[$rid])) {
                $missingReceiptIds[] = $rid;
            }
        }

        if ($validIdsFromDb === []) {
            return Response::structured([
                'receipts' => [],
                'missing_receipt_ids' => $missingReceiptIds,
            ]);
        }

        $items = ReceiptItem::query()
            ->whereIn('receipt_id', $validIdsFromDb)
            ->with('category')
            ->orderBy('receipt_id')
            ->orderBy('id')
            ->get();

        /** @var array<int, list<array<string, mixed>>> $byReceiptId */
        $byReceiptId = [];

        foreach ($items as $item) {
            $byReceiptId[$item->receipt_id][] = ReceiptMcpItemPayload::row($item);
        }

        $receipts = [];

        foreach ($requested as $rid) {
            if (!isset($validSet[$rid])) {
                continue;
            }

            $receipts[] = [
                'receipt_id' => $rid,
                'items' => $byReceiptId[$rid] ?? [],
            ];
        }

        return Response::structured([
            'receipts' => $receipts,
            'missing_receipt_ids' => $missingReceiptIds,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'receipt_ids' => $schema->array()
                ->items($schema->integer())
                ->min(1)
                ->max(self::MAX_RECEIPTS)
                ->description('Receipt ids to load line items for (max ' . self::MAX_RECEIPTS . ', distinct).')
                ->required(),
        ];
    }
}
