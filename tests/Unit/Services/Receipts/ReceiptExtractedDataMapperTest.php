<?php

declare(strict_types=1);

use App\Models\ReceiptCategory;
use App\Models\User;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;
use App\Services\Receipts\ReceiptExtractedDataMapper;

\it('maps n8n output to receipt header and line items', function (): void {
    $user = User::factory()->create();
    ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);

    $mapper = new ReceiptExtractedDataMapper();
    $mapped = $mapper->map(
        $user,
        [
            'date' => '2026-05-01',
            'time' => '14:30:00',
            'vendor' => 'Coffee Shop',
            'items' => [
                [
                    'description' => 'Latte',
                    'quantity' => 2,
                    'price' => 100.0,
                    'category' => 'groceries',
                ],
            ],
        ],
        filePath: 'receipts/test.jpg',
        description: 'Mail body notes',
    );

    \expect($mapped->header['name'])->toBe('Coffee Shop')
        ->and($mapped->header['date'])->toBe('2026-05-01T14:30')
        ->and($mapped->header['file_path'])->toBe('receipts/test.jpg')
        ->and($mapped->header['description'])->toBe('Mail body notes')
        ->and($mapped->items)->toHaveCount(1)
        ->and($mapped->items[0]['name'])->toBe('Latte')
        ->and($mapped->items[0]['quantity'])->toBe(2)
        ->and($mapped->items[0]['amount'])->toBe(50.0);
});

\it('coerces string prices and quantities from n8n', function (): void {
    $user = User::factory()->create();
    ReceiptCategory::factory()->for($user)->create(['name' => 'Groceries']);

    $mapper = new ReceiptExtractedDataMapper();
    $mapped = $mapper->map($user, [
        'date' => '2026-05-01',
        'items' => [
            ['description' => 'Item', 'quantity' => '2', 'price' => '100.00'],
        ],
    ]);

    \expect($mapped->items[0]['quantity'])->toBe(2)
        ->and($mapped->items[0]['amount'])->toBe(50.0);
});

\it('requires at least one receipt category', function (): void {
    $user = User::factory()->create();
    $mapper = new ReceiptExtractedDataMapper();

    $mapper->map($user, [
        'date' => '2026-05-01',
        'items' => [
            ['description' => 'Item', 'quantity' => 1, 'price' => 10.0],
        ],
    ]);
})->throws(ReceiptExtractionException::class, 'Create at least one receipt category');
