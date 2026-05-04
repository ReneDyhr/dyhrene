<?php

declare(strict_types=1);

use App\Livewire\Settings\McpConnection;
use App\Mcp\Servers\ShoppingListServer;
use App\Models\ShoppingList;
use App\Models\User;
use Laravel\Passport\Passport;

\uses()->group('feature');

\test('mcp shopping list returns 401 without authentication', function (): void {
    $response = $this->postJson('/mcp/shopping-list', [
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
})->covers(ShoppingListServer::class);

\test('mcp shopping list initialize succeeds with mcp use scope', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['mcp:use']);

    $response = $this->postJson('/mcp/shopping-list', [
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

    $response->assertStatus(200);
    $response->assertHeader('MCP-Session-Id');
    $response->assertJsonPath('result.serverInfo.name', 'Shopping List Server');
})->covers(ShoppingListServer::class);

\test('mcp shopping list add item tool persists row', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['mcp:use']);

    $init = $this->postJson('/mcp/shopping-list', [
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

    $call = $this->postJson('/mcp/shopping-list', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'shopping_list_add_item',
            'arguments' => [
                'name' => 'Organic milk',
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);

    $this->assertDatabaseHas('shopping_list', [
        'user_id' => $user->id,
        'name' => 'Organic milk',
        'status' => 'active',
    ]);
})->covers(ShoppingListServer::class);

\test('mcp shopping list check item rejects section header', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['mcp:use']);

    $row = new ShoppingList();
    $row->user_id = $user->id;
    $row->name = '#Dairy';
    $row->order = 1;
    $row->status = 'active';
    $row->save();

    $init = $this->postJson('/mcp/shopping-list', [
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

    $call = $this->postJson('/mcp/shopping-list', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'shopping_list_check_item',
            'arguments' => [
                'id' => $row->id,
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);
    $call->assertJsonPath('result.structuredContent.updated', false);
    $call->assertJsonPath('result.structuredContent.reason', 'section_header');

    $this->assertDatabaseHas('shopping_list', [
        'id' => $row->id,
        'status' => 'active',
    ]);
})->covers(ShoppingListServer::class);

\test('mcp shopping list uncheck item rejects section header', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['mcp:use']);

    $row = new ShoppingList();
    $row->user_id = $user->id;
    $row->name = '#Dairy';
    $row->order = 1;
    $row->status = 'checked';
    $row->save();

    $init = $this->postJson('/mcp/shopping-list', [
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

    $call = $this->postJson('/mcp/shopping-list', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'shopping_list_uncheck_item',
            'arguments' => [
                'id' => $row->id,
            ],
        ],
    ], [
        'MCP-Session-Id' => $sessionId,
    ]);

    $call->assertStatus(200);
    $call->assertJsonPath('result.isError', false);
    $call->assertJsonPath('result.structuredContent.updated', false);
    $call->assertJsonPath('result.structuredContent.reason', 'section_header');

    $this->assertDatabaseHas('shopping_list', [
        'id' => $row->id,
        'status' => 'checked',
    ]);
})->covers(ShoppingListServer::class);

\test('settings mcp page requires authentication', function (): void {
    $this->get(\route('settings.mcp'))->assertRedirect();
})->covers(McpConnection::class);

\test('settings mcp page is reachable when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(\route('settings.mcp'))
        ->assertStatus(200)
        ->assertSeeText('MCP & OAuth (AI assistants)', false)
        ->assertSeeText('Shopping List', false)
        ->assertSeeText('Receipts', false)
        ->assertSeeText('Shared OAuth (all MCP servers)', false);
})->covers(McpConnection::class);
