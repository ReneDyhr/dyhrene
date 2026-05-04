<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Actions\CreateReceiptAction;
use App\Mcp\Receipts\ReceiptMcpImageStorage;
use App\Models\User;
use App\Support\ReceiptDuplicateGuard;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name(value: 'receipt_create')]
#[Description(value: 'Create a receipt with line items and a required documentation image (base64 + mime). Stores image like the web app (Wasabi).')]
class CreateReceiptTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return Response::error('Unauthenticated.');
        }

        /** @var array{name: string, vendor?: null|string, description?: null|string, currency: string, date: string, items: list<array{name: string, quantity: int, amount: float|int, category_id: int}>, image_base64: string, image_mime_type: string} $validated */
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'required|string|max:10',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|not_in:0',
            'items.*.amount' => 'required|numeric',
            'items.*.category_id' => [
                'required',
                'integer',
                Rule::exists('receipt_categories', 'id')->where('user_id', $userId),
            ],
            'image_base64' => 'required|string',
            'image_mime_type' => ['required', 'string', Rule::in(ReceiptMcpImageStorage::ALLOWED_MIMES)],
        ]);

        $total = 0.0;

        foreach ($validated['items'] as $item) {
            $total += $item['amount'] * $item['quantity'];
        }

        try {
            $receiptDate = Carbon::parse($validated['date']);
        } catch (\Throwable) {
            return Response::error('Invalid receipt date.');
        }

        if (ReceiptDuplicateGuard::duplicateExists($user, $validated['vendor'] ?? null, $receiptDate, $total)) {
            return Response::error('A receipt with the same vendor, time, and total price already exists.');
        }

        try {
            $filePath = ReceiptMcpImageStorage::storeFromBase64(
                $validated['image_base64'],
                $validated['image_mime_type'],
            );
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        $data = [
            'name' => $validated['name'],
            'vendor' => $validated['vendor'] ?? null,
            'description' => $validated['description'] ?? null,
            'currency' => $validated['currency'],
            'date' => $receiptDate->format('Y-m-d H:i:s'),
            'file_path' => $filePath,
        ];

        $receipt = \app(CreateReceiptAction::class)->handle($user, $data);

        foreach ($validated['items'] as $item) {
            $receipt->items()->create([
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
                'category_id' => $item['category_id'],
            ]);
        }

        $receipt->load('items');

        return Response::structured([
            'id' => $receipt->id,
            'name' => $receipt->name,
            'vendor' => $receipt->vendor,
            'currency' => $receipt->currency,
            'date' => $receipt->date->toIso8601String(),
            'file_path' => $receipt->file_path,
            'item_count' => $receipt->items()->count(),
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
            'name' => $schema->string()->required(),
            'vendor' => $schema->string()->description('Optional store or merchant name.'),
            'description' => $schema->string()->description('Optional notes.'),
            'currency' => $schema->string()->description('Currency label or code (e.g. DKK, kr.).')->required(),
            'date' => $schema->string()->description('Receipt date/time (ISO 8601 or parseable by Carbon).')->required(),
            'items' => $schema->array()->items($itemSchema)->min(1)->description('At least one line item.')->required(),
            'image_base64' => $schema->string()->description('Base64-encoded receipt image or PDF (max 15 MiB decoded). Data-URL prefix optional.')->required(),
            'image_mime_type' => $schema->string()->description('One of: image/jpeg, image/png, application/pdf.')->required(),
        ];
    }
}
