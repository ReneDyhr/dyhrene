<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'receipt_list')]
#[Description(value: 'List receipts for the authenticated user (summaries only; no line items). Optional from/to YYYY-MM-DD inclusive filter on receipt date.')]
#[IsReadOnly(value: true)]
class ListReceiptsTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{from?: string, to?: string} $validated */
        $validated = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);

        $query = Receipt::forAuthUser()
            ->withCount('items')
            ->orderByDesc('date');

        if (isset($validated['from'])) {
            $from = Carbon::parse($validated['from'])->startOfDay();
            $query->where('date', '>=', $from);
        }

        if (isset($validated['to'])) {
            $to = Carbon::parse($validated['to'])->endOfDay();
            $query->where('date', '<=', $to);
        }

        $receipts = $query->get([
            'id',
            'name',
            'vendor',
            'description',
            'currency',
            'date',
            'file_path',
        ]);

        $ids = $receipts->pluck('id')->all();

        $lineTotalsByReceiptId = [];

        if ($ids !== []) {
            foreach (DB::table('receipt_items')
                ->whereIn('receipt_id', $ids)
                ->groupBy('receipt_id')
                ->selectRaw('receipt_id, SUM(amount * quantity) as line_total')
                ->cursor() as $row) {
                $parsed = \filter_var($row->line_total, \FILTER_VALIDATE_FLOAT);

                if ($parsed !== false) {
                    $lineTotalsByReceiptId[(int) $row->receipt_id] = $parsed;
                }
            }
        }

        $out = $receipts->map(function (Receipt $r) use ($lineTotalsByReceiptId): array {
            $tid = $r->id;
            $total = $lineTotalsByReceiptId[$tid] ?? 0.0;

            return [
                'id' => $r->id,
                'name' => $r->name,
                'vendor' => $r->vendor,
                'description' => $r->description,
                'currency' => $r->currency,
                'date' => $r->date->toIso8601String(),
                'has_file' => $r->file_path !== null && $r->file_path !== '',
                'item_count' => $r->items_count,
                'total' => $total,
            ];
        })->values()->all();

        return Response::structured(['receipts' => $out]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('Optional start date (YYYY-MM-DD), inclusive.'),
            'to' => $schema->string()->description('Optional end date (YYYY-MM-DD), inclusive.'),
        ];
    }
}
