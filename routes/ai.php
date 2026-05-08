<?php

declare(strict_types=1);

use App\Mcp\Receipts\ReceiptsMcpRoute;
use App\Mcp\Recipes\RecipesMcpRoute;
use App\Mcp\Servers\ReceiptServer;
use App\Mcp\Servers\RecipeServer;
use App\Mcp\Servers\ShoppingListServer;
use App\Mcp\ShoppingList\ShoppingListMcpRoute;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Http\Middleware\CheckToken;

Mcp::oauthRoutes();

Mcp::web(ShoppingListMcpRoute::PATH, ShoppingListServer::class)
    ->middleware(['auth:api', CheckToken::using('mcp:use')]);

Mcp::web(ReceiptsMcpRoute::PATH, ReceiptServer::class)
    ->middleware(['auth:api', CheckToken::using('mcp:use')]);

Mcp::web(RecipesMcpRoute::PATH, RecipeServer::class)
    ->middleware(['auth:api', CheckToken::using('mcp:use')]);
