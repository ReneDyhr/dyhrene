<?php

declare(strict_types=1);

namespace App\Mcp\Receipts;

final class ReceiptsMcpRoute
{
    public const PATH = 'mcp/receipts';

    public static function endpointUrl(): string
    {
        return \url('/' . self::PATH);
    }
}
