<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Models\ReceiptCategory;
use App\Models\User;
use App\Services\Receipts\DTOs\MappedReceiptData;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;

final class ReceiptExtractedDataMapper
{
    /**
     * @param array<string, mixed> $output n8n output payload
     */
    public function map(
        User $user,
        array $output,
        string $defaultCurrency = 'kr.',
        ?string $filePath = null,
        ?string $description = null,
        ?string $fallbackName = null,
        ?string $fallbackVendor = null,
        ?string $fallbackDate = null,
    ): MappedReceiptData {
        if (!isset($output['items']) || !\is_array($output['items']) || $output['items'] === []) {
            throw new ReceiptExtractionException('Could not extract items from receipt.');
        }

        $categories = ReceiptCategory::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($categories->isEmpty()) {
            throw new ReceiptExtractionException('Create at least one receipt category before importing receipts.');
        }

        $categoryMap = $categories->mapWithKeys(
            static fn(ReceiptCategory $cat): array => [\mb_strtolower($cat->name) => $cat->id],
        );

        $defaultCategoryId = $categories->first()->id;

        $date = isset($output['date']) && \is_string($output['date']) ? $output['date'] : null;
        $time = isset($output['time']) && \is_string($output['time']) ? $output['time'] : null;
        $mappedDate = $fallbackDate;

        if ($date !== null && $time !== null) {
            $mappedDate = $date . 'T' . \substr($time, 0, 5);
        } elseif ($date !== null) {
            $mappedDate = $date;
        }

        if ($mappedDate === null || $mappedDate === '') {
            throw new ReceiptExtractionException('Could not determine receipt date from extraction.');
        }

        $vendor = isset($output['vendor']) && \is_string($output['vendor'])
            ? $output['vendor']
            : $fallbackVendor;
        $name = $vendor ?? $fallbackName ?? 'Receipt';

        if ($name === '') {
            $name = 'Receipt';
        }

        $items = [];

        foreach ($output['items'] as $itemRaw) {
            if (!\is_array($itemRaw)) {
                continue;
            }

            $itemName = $this->itemName($this->stringKeyedRow($itemRaw));
            $quantity = $this->coercePositiveInt($itemRaw['quantity'] ?? null, 1);
            $price = $this->coerceNumber($itemRaw['price'] ?? $itemRaw['amount'] ?? null);
            $catName = isset($itemRaw['category']) && \is_string($itemRaw['category'])
                ? \mb_strtolower($itemRaw['category'])
                : '';
            $categoryId = $categoryMap[$catName] ?? $defaultCategoryId;

            $items[] = [
                'name' => $itemName,
                'quantity' => $quantity,
                'amount' => $quantity !== 0 ? $price / $quantity : $price,
                'category_id' => $categoryId,
            ];
        }

        if ($items === []) {
            throw new ReceiptExtractionException('Could not extract items from receipt.');
        }

        $header = [
            'name' => $name,
            'vendor' => $vendor,
            'description' => $description,
            'currency' => $defaultCurrency,
            'date' => $mappedDate,
        ];

        if ($filePath !== null && $filePath !== '') {
            $header['file_path'] = $filePath;
        }

        return new MappedReceiptData($header, $items);
    }

    /**
     * @param array<mixed> $row
     *
     * @return array<string, mixed>
     */
    private function stringKeyedRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $itemRaw
     */
    private function itemName(array $itemRaw): string
    {
        foreach (['description', 'name', 'title'] as $key) {
            if (isset($itemRaw[$key]) && \is_string($itemRaw[$key]) && $itemRaw[$key] !== '') {
                return $itemRaw[$key];
            }
        }

        return '';
    }

    private function coerceNumber(mixed $value): float
    {
        if (\is_int($value) || \is_float($value)) {
            return (float) $value;
        }

        if (\is_string($value) && \is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }

    private function coercePositiveInt(mixed $value, int $default): int
    {
        if (\is_int($value)) {
            return $value > 0 ? $value : $default;
        }

        if (\is_float($value)) {
            $asInt = (int) $value;

            return $asInt > 0 ? $asInt : $default;
        }

        if (\is_string($value) && \is_numeric($value)) {
            $asInt = (int) $value;

            return $asInt > 0 ? $asInt : $default;
        }

        return $default;
    }
}
