<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Actions\UpdateReceiptAction;
use App\Mcp\Receipts\ReceiptMcpImageStorage;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name(value: 'receipt_update')]
#[Description(value: 'Update receipt header fields (metadata only). Optional image_base64 + image_mime_type replaces the stored scan. Omit fields you do not want to change.')]
class UpdateReceiptTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'receipt_id' => 'required|integer',
            'name' => 'sometimes|nullable|string|max:255',
            'vendor' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'currency' => 'sometimes|nullable|string|max:10',
            'date' => 'sometimes|nullable|date',
            'image_base64' => 'sometimes|nullable|string',
            'image_mime_type' => ['sometimes', 'nullable', 'string', 'required_with:image_base64', Rule::in(ReceiptMcpImageStorage::ALLOWED_MIMES)],
        ]);

        $receipt = Receipt::forAuthUser()->whereKey($validated['receipt_id'])->first();

        if ($receipt === null) {
            return Response::error('Receipt not found.');
        }

        $data = Arr::except($validated, ['receipt_id', 'image_base64', 'image_mime_type']);
        $data = \array_filter(
            $data,
            static fn(mixed $v): bool => $v !== null,
        );

        if (\array_key_exists('date', $data)) {
            $dateValue = $data['date'];

            if (!\is_string($dateValue) && !$dateValue instanceof \DateTimeInterface) {
                return Response::error('Invalid receipt date.');
            }

            try {
                $data['date'] = Carbon::parse($dateValue)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return Response::error('Invalid receipt date.');
            }
        }

        if (!empty($validated['image_base64']) && \is_string($validated['image_base64'])) {
            try {
                $mimeType = $validated['image_mime_type'] ?? '';
                $mime = \is_string($mimeType) ? $mimeType : '';
                $data['file_path'] = ReceiptMcpImageStorage::storeFromBase64($validated['image_base64'], $mime);
            } catch (\InvalidArgumentException $e) {
                return Response::error($e->getMessage());
            }
        }

        if ($data === []) {
            return Response::structured(['updated' => false, 'message' => 'No fields to update.']);
        }

        \app(UpdateReceiptAction::class)->handle($receipt, $data);

        $receipt->refresh();

        return Response::structured([
            'updated' => true,
            'id' => $receipt->id,
            'name' => $receipt->name,
            'vendor' => $receipt->vendor,
            'description' => $receipt->description,
            'currency' => $receipt->currency,
            'date' => $receipt->date->toIso8601String(),
            'file_path' => $receipt->file_path,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'receipt_id' => $schema->integer()->required(),
            'name' => $schema->string()->description('New receipt title (omit if unchanged).'),
            'vendor' => $schema->string()->description('Vendor name (omit if unchanged).'),
            'description' => $schema->string()->description('Notes (omit if unchanged).'),
            'currency' => $schema->string()->description('Currency (omit if unchanged).'),
            'date' => $schema->string()->description('Receipt date/time (omit if unchanged).'),
            'image_base64' => $schema->string()->description('Optional new image/PDF as base64 (replaces stored file).'),
            'image_mime_type' => $schema->string()->description('Required with image_base64: image/jpeg, image/png, or application/pdf.'),
        ];
    }
}
