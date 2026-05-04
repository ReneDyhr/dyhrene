<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Models\Receipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'receipt_get_image')]
#[Description(value: 'Return the stored receipt image/PDF for a receipt (binary MCP image content when format is supported).')]
#[IsReadOnly(value: true)]
class GetReceiptImageTool extends Tool
{
    public function handle(Request $request): Response
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

        $path = $receipt->file_path;

        if ($path === null || $path === '' || !Storage::disk('wasabi')->exists($path)) {
            return Response::error('No stored image for this receipt.');
        }

        try {
            return Response::fromStorage($path, 'wasabi');
        } catch (\Throwable $e) {
            return Response::error('Failed to read receipt image: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'receipt_id' => $schema->integer()->required(),
        ];
    }
}
