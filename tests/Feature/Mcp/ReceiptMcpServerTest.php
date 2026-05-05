<?php

declare(strict_types=1);

use App\Mcp\Servers\ReceiptServer;
use App\Models\Receipt;
use App\Models\ReceiptCategory;
use App\Models\ReceiptItem;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

\uses()->group('feature');

/**
 * @return non-empty-string
 */
function receiptMcpSessionId(TestCase $test, User $user): string
{
    Passport::actingAs($user, ['mcp:use']);

    $init = $test->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test',
                'version' => '0.0.1',
            ],
        ],
    ]);

    $init->assertStatus(200);
    $sessionId = $init->headers->get('MCP-Session-Id');
    \expect($sessionId)->not->toBeEmpty();

    return (string) $sessionId;
}

\test('mcp receipts returns 401 without authentication', function (): void {
    $response = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test',
                'version' => '0.0.1',
            ],
        ],
    ]);

    $response->assertStatus(401);
})->covers(ReceiptServer::class);

\test('mcp receipts initialize succeeds with mcp use scope', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['mcp:use']);

    $init = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test',
                'version' => '0.0.1',
            ],
        ],
    ]);

    $init->assertStatus(200);
    $init->assertHeader('MCP-Session-Id');
    $init->assertJsonPath('result.serverInfo.name', 'Receipts Server');
})->covers(ReceiptServer::class);

\test('mcp receipt_list_categories returns user categories', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);
    $sessionId = \receiptMcpSessionId($this, $user);

    $call = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_list_categories',
            'arguments' => [],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);
    $call->assertJsonPath('result.structuredContent.categories.0.name', 'Groceries');
})->covers(ReceiptServer::class);

\test('mcp receipt_list returns summaries and respects from to', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    $cat = ReceiptCategory::factory()->for($user)->create();

    $old = Receipt::factory()->for($user)->create([
        'date' => '2020-01-15 12:00:00',
    ]);
    ReceiptItem::factory()->for($old)->create(['category_id' => $cat->id, 'quantity' => 2, 'amount' => 5.00]);

    $new = Receipt::factory()->for($user)->create([
        'date' => '2025-06-10 14:00:00',
    ]);
    ReceiptItem::factory()->for($new)->create(['category_id' => $cat->id, 'quantity' => 1, 'amount' => 10.00]);

    $sessionId = \receiptMcpSessionId($this, $user);

    $call = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_list',
            'arguments' => [
                'from' => '2025-06-01',
                'to' => '2025-06-30',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);
    $call->assertJsonPath('result.structuredContent.receipts.0.id', $new->id);
    $call->assertJsonPath('result.structuredContent.receipts.0.item_count', 1);
    $call->assertJsonPath('result.structuredContent.receipts.0.total', 10);
    $ids = \collect($call->json('result.structuredContent.receipts'))->pluck('id')->all();
    \expect($ids)->not->toContain($old->id);
})->covers(ReceiptServer::class);

\test('mcp receipt_get_items returns line items', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    $cat = ReceiptCategory::factory()->for($user)->create(['name' => 'Food']);
    $receipt = Receipt::factory()->for($user)->create();
    ReceiptItem::factory()->for($receipt)->create([
        'name' => 'Apples',
        'quantity' => 3,
        'amount' => 2.5,
        'category_id' => $cat->id,
    ]);

    $sessionId = \receiptMcpSessionId($this, $user);

    $call = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_get_items',
            'arguments' => [
                'receipt_id' => $receipt->id,
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);
    $call->assertJsonPath('result.structuredContent.items.0.name', 'Apples');
    $call->assertJsonPath('result.structuredContent.items.0.category_name', 'Food');
})->covers(ReceiptServer::class);

\test('mcp receipt_get_items_batch returns items for multiple receipts', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    $cat = ReceiptCategory::factory()->for($user)->create();

    $r1 = Receipt::factory()->for($user)->create();
    ReceiptItem::factory()->for($r1)->create(['name' => 'A', 'quantity' => 1, 'amount' => 1.0, 'category_id' => $cat->id]);

    $r2 = Receipt::factory()->for($user)->create();
    ReceiptItem::factory()->for($r2)->create(['name' => 'B', 'quantity' => 2, 'amount' => 3.0, 'category_id' => $cat->id]);

    $sessionId = \receiptMcpSessionId($this, $user);
    $ghostId = 9_999_999;

    $call = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_get_items_batch',
            'arguments' => [
                'receipt_ids' => [$r2->id, $r1->id, $ghostId],
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);
    $call->assertJsonPath('result.structuredContent.receipts.0.receipt_id', $r2->id);
    $call->assertJsonPath('result.structuredContent.receipts.0.items.0.name', 'B');
    $call->assertJsonPath('result.structuredContent.receipts.1.receipt_id', $r1->id);
    $call->assertJsonPath('result.structuredContent.receipts.1.items.0.name', 'A');
    $call->assertJsonPath('result.structuredContent.missing_receipt_ids.0', $ghostId);
})->covers(ReceiptServer::class);

\test('mcp receipt_create stores receipt image and items', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    $cat = ReceiptCategory::factory()->for($user)->create();
    $sessionId = \receiptMcpSessionId($this, $user);

    $tinyPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    $call = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_create',
            'arguments' => [
                'name' => 'Test Mart',
                'vendor' => 'Test Mart',
                'currency' => 'USD',
                'date' => '2025-07-01T10:00:00',
                'items' => [
                    [
                        'name' => 'Milk',
                        'quantity' => 1,
                        'amount' => 4.99,
                        'category_id' => $cat->id,
                    ],
                ],
                'image_base64' => $tinyPng,
                'image_mime_type' => 'image/png',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);
    $rid = (int) $call->json('result.structuredContent.id');
    \expect($rid)->toBeGreaterThan(0);

    $receipt = Receipt::query()->findOrFail($rid);
    \expect($receipt->file_path)->not->toBeEmpty();
    Storage::disk('wasabi')->assertExists((string) $receipt->file_path);
    \expect($receipt->items)->toHaveCount(1);
})->covers(ReceiptServer::class);

\test('mcp receipt_update and receipt_items_update', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    $cat = ReceiptCategory::factory()->for($user)->create();
    $sessionId = \receiptMcpSessionId($this, $user);

    $tinyPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    $create = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_create',
            'arguments' => [
                'name' => 'Shop',
                'currency' => 'DKK',
                'date' => '2025-08-01T09:00:00',
                'items' => [
                    [
                        'name' => 'A',
                        'quantity' => 1,
                        'amount' => 1.0,
                        'category_id' => $cat->id,
                    ],
                ],
                'image_base64' => $tinyPng,
                'image_mime_type' => 'image/png',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);
    $create->assertStatus(200);
    $rid = (int) $create->json('result.structuredContent.id');

    $upd = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_update',
            'arguments' => [
                'receipt_id' => $rid,
                'description' => 'Updated notes',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);
    $upd->assertStatus(200);
    $upd->assertJsonPath('result.structuredContent.description', 'Updated notes');

    $items = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 4,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_items_update',
            'arguments' => [
                'receipt_id' => $rid,
                'items' => [
                    [
                        'name' => 'B',
                        'quantity' => 2,
                        'amount' => 3.0,
                        'category_id' => $cat->id,
                    ],
                ],
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);
    $items->assertStatus(200);
    $items->assertJsonPath('result.structuredContent.total', 6);

    $fresh = Receipt::query()->with('items')->findOrFail($rid);
    \expect($fresh->items)->toHaveCount(1);
    \expect($fresh->items[0]->name)->toBe('B');
})->covers(ReceiptServer::class);

\test('mcp receipt_get_image succeeds when file exists', function (): void {
    Storage::fake('wasabi');
    $user = User::factory()->create();
    $cat = ReceiptCategory::factory()->for($user)->create();
    $sessionId = \receiptMcpSessionId($this, $user);

    $tinyPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    $create = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_create',
            'arguments' => [
                'name' => 'Img Shop',
                'currency' => 'EUR',
                'date' => '2025-09-01T11:00:00',
                'items' => [
                    [
                        'name' => 'X',
                        'quantity' => 1,
                        'amount' => 1.0,
                        'category_id' => $cat->id,
                    ],
                ],
                'image_base64' => $tinyPng,
                'image_mime_type' => 'image/png',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);
    $create->assertStatus(200);
    $rid = (int) $create->json('result.structuredContent.id');

    $img = $this->postJson('/mcp/receipts', [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'receipt_get_image',
            'arguments' => [
                'receipt_id' => $rid,
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $img->assertStatus(200);
    $img->assertJsonPath('result.isError', false);
})->covers(ReceiptServer::class);
