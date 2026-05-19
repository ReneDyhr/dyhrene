<?php

declare(strict_types=1);

use App\Services\Receipts\Exceptions\ReceiptExtractionException;
use App\Services\Receipts\N8nReceiptExtractor;
use Illuminate\Support\Facades\Http;

\beforeEach(function (): void {
    \config(['n8n.webhook_url' => 'https://n8n.example.com/webhook/receipt']);
});

\it('returns output payload from n8n webhook', function (): void {
    Http::fake([
        'https://n8n.example.com/webhook/receipt' => Http::response([
            'output' => [
                'date' => '2026-05-01',
                'vendor' => 'Shop',
                'items' => [
                    ['description' => 'Coffee', 'quantity' => 1, 'price' => 25.0],
                ],
            ],
        ]),
    ]);

    $extractor = new N8nReceiptExtractor();
    $output = $extractor->extract('image-bytes', 'receipt.jpg');

    \expect($output['vendor'])->toBe('Shop')
        ->and($output['items'])->toHaveCount(1);
});

\it('throws when webhook url is not configured', function (): void {
    \config(['n8n.webhook_url' => null]);

    $extractor = new N8nReceiptExtractor();

    $extractor->extract('bytes', 'file.jpg');
})->throws(ReceiptExtractionException::class, 'n8n webhook URL is not configured');

\it('unwraps n8n array webhook responses', function (): void {
    Http::fake([
        'https://n8n.example.com/webhook/receipt' => Http::response([
            [
                'json' => [
                    'output' => [
                        'date' => '2026-05-01',
                        'vendor' => 'Shop',
                        'items' => [
                            ['description' => 'Item', 'quantity' => 1, 'price' => '12.50'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $extractor = new N8nReceiptExtractor();
    $output = $extractor->extract('image-bytes', 'receipt.jpg');

    \expect($output['vendor'])->toBe('Shop')
        ->and($output['items'])->toHaveCount(1);
});

\it('throws when webhook response has no items', function (): void {
    Http::fake([
        'https://n8n.example.com/webhook/receipt' => Http::response([
            'output' => ['date' => '2026-05-01'],
        ]),
    ]);

    $extractor = new N8nReceiptExtractor();

    $extractor->extract('bytes', 'file.jpg');
})->throws(ReceiptExtractionException::class, 'Could not extract items from receipt');
