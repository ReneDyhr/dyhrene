<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\Receipts\CreateReceiptTool;
use App\Mcp\Tools\Receipts\GetReceiptImageTool;
use App\Mcp\Tools\Receipts\GetReceiptItemsTool;
use App\Mcp\Tools\Receipts\ListReceiptCategoriesTool;
use App\Mcp\Tools\Receipts\ListReceiptsTool;
use App\Mcp\Tools\Receipts\UpdateReceiptItemsTool;
use App\Mcp\Tools\Receipts\UpdateReceiptTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name(value: 'Receipts Server')]
#[Version(value: '0.1.0')]
#[Instructions(value: <<<'MARKDOWN'
This server manages the signed-in user's receipts (metadata, line items, and stored documentation images).

**Authentication:** OAuth 2.1 via Laravel Passport. Clients must obtain an access token that includes the **`mcp:use`** scope, then send `Authorization: Bearer <token>` on every MCP HTTP request.

**Tools:** Call **`receipt_list_categories`** when you need valid **`category_id`** values for line items. **`receipt_list`** returns receipt summaries only (no line items) to keep context small; optional **`from`** / **`to`** filters use **YYYY-MM-DD** inclusive. Use **`receipt_get_items`** with **`receipt_id`** to load line items. **`receipt_create`** requires header fields, at least one line item, and a **documentation image**: **`image_base64`** plus **`image_mime_type`** (`image/jpeg`, `image/png`, or `application/pdf`; max **15 MiB** decoded). **`receipt_update`** changes receipt metadata only; **`receipt_items_update`** replaces **all** line items for a receipt. **`receipt_get_image`** returns the stored scan for multimodal clients.

**MCP endpoint:** HTTP `POST` to `/mcp/receipts` on this app’s base URL (copy from the web app under **MCP & OAuth (AI assistants)** in the side menu).
MARKDOWN)]
class ReceiptServer extends Server
{
    protected array $tools = [
        ListReceiptCategoriesTool::class,
        ListReceiptsTool::class,
        GetReceiptItemsTool::class,
        CreateReceiptTool::class,
        UpdateReceiptTool::class,
        UpdateReceiptItemsTool::class,
        GetReceiptImageTool::class,
    ];

    protected array $resources = [
    ];

    protected array $prompts = [
    ];
}
