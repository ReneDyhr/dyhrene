<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Receipts;

use App\Models\ReceiptCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name(value: 'receipt_list_categories')]
#[Description(value: 'List receipt categories for the authenticated user (id and name for line-item category_id).')]
#[IsReadOnly(value: true)]
class ListReceiptCategoriesTool extends Tool
{
    public function handle(Request $request): Response | ResponseFactory
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            return Response::error('Unauthenticated.');
        }

        $rows = ReceiptCategory::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Response::structured([
            'categories' => $rows->map(static fn(ReceiptCategory $c): array => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
