<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Mcp\Receipts\ReceiptsMcpRoute;
use App\Mcp\Recipes\RecipesMcpRoute;
use App\Mcp\ShoppingList\ShoppingListMcpRoute;

/**
 * Declarative list of HTTP MCP servers exposed by this app.
 * When you add a new `Mcp::web(...)` route, register the same path here so the settings UI shows connection URLs.
 */
final class McpServerRegistry
{
    /**
     * @return list<array{id: string, title: string, description: string, path: string}>
     */
    public static function servers(): array
    {
        return [
            [
                'id' => 'shopping-list',
                'title' => 'Shopping List',
                'description' => 'AI clients can list, add, remove, check, uncheck, and reorder items for the signed-in user.',
                'path' => ShoppingListMcpRoute::PATH,
            ],
            [
                'id' => 'receipts',
                'title' => 'Receipts',
                'description' => 'AI clients can list receipts, fetch line items, create receipts with images, and update metadata or line items.',
                'path' => ReceiptsMcpRoute::PATH,
            ],
            [
                'id' => 'recipes',
                'title' => 'Recipes',
                'description' => 'AI clients can list, filter, search, create, edit, and delete recipes for the signed-in user.',
                'path' => RecipesMcpRoute::PATH,
            ],
        ];
    }

    /**
     * Values common to every MCP HTTP server (same Passport OAuth server).
     *
     * @return array<string, string>
     */
    public static function sharedOAuthFields(): array
    {
        return [
            'Required OAuth scope for MCP access tokens' => 'mcp:use',
            'OAuth authorization server metadata (GET)' => \route('mcp.oauth.authorization-server'),
            'OAuth dynamic client registration (POST body: client metadata)' => \url('oauth/register'),
            'Passport authorization endpoint (browser)' => \route('passport.authorizations.authorize'),
            'Passport token endpoint (POST)' => \route('passport.token'),
        ];
    }

    /**
     * URLs that depend on the MCP resource path (each server has its own POST URL and protected-resource metadata).
     *
     * @return array<string, string>
     */
    public static function serverResourceFields(string $path): array
    {
        return [
            'MCP HTTP endpoint (POST JSON-RPC)' => \url('/' . $path),
            'OAuth protected resource metadata (GET)' => \route('mcp.oauth.protected-resource.nested', ['path' => $path]),
        ];
    }
}
