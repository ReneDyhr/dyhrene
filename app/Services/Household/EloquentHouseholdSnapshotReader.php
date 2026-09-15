<?php

declare(strict_types=1);

namespace App\Services\Household;

use App\Domain\Household\Contracts\HouseholdSnapshotReaderInterface;
use App\Domain\Household\HouseholdSnapshot;
use App\Domain\Household\ReceiptSummary;
use App\Domain\Overview\InventoryOverview;
use App\Domain\Overview\LatestReceipt;
use App\Domain\Overview\ReceiptOverview;
use App\Models\InventoryItem;
use App\Models\Receipt;
use Illuminate\Support\Collection;

final class EloquentHouseholdSnapshotReader implements HouseholdSnapshotReaderInterface
{
    public function read(int $userId, \DateTimeImmutable $now): HouseholdSnapshot
    {
        $monthStart = $now->setTime(0, 0)->modify('first day of this month');
        $nextMonthStart = $monthStart->modify('+1 month');

        $currentMonthReceipts = Receipt::query()
            ->where('user_id', $userId)
            ->where('date', '>=', $monthStart)
            ->where('date', '<', $nextMonthStart)
            ->with('items')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
        $latestReceipt = $currentMonthReceipts->first();

        /** @var array<string, float> $amountsByCurrency */
        $amountsByCurrency = [];

        foreach ($currentMonthReceipts as $receipt) {
            $amountsByCurrency[$receipt->currency] = ($amountsByCurrency[$receipt->currency] ?? 0.0) + $receipt->total;
        }

        \ksort($amountsByCurrency);

        return new HouseholdSnapshot(
            receipts: new ReceiptOverview(
                currentMonthCount: $currentMonthReceipts->count(),
                latest: $latestReceipt instanceof Receipt ? new LatestReceipt(
                    id: $latestReceipt->id,
                    name: $latestReceipt->name,
                    vendor: $latestReceipt->vendor,
                    currency: $latestReceipt->currency,
                    date: $this->toImmutable($latestReceipt->date),
                    amount: $latestReceipt->total,
                ) : null,
                amountsByCurrency: $amountsByCurrency,
            ),
            inventory: new InventoryOverview(
                count: InventoryItem::query()->where('user_id', $userId)->count(),
            ),
            latestReceipts: $this->toSummaries(
                Receipt::query()
                    ->where('user_id', $userId)
                    ->with('items.category')
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(),
            ),
        );
    }

    /**
     * @param  Collection<int, Receipt> $receipts
     * @return list<ReceiptSummary>
     */
    private function toSummaries(Collection $receipts): array
    {
        $summaries = [];

        foreach ($receipts as $receipt) {
            $firstItem = $receipt->items->first();

            $summaries[] = new ReceiptSummary(
                id: $receipt->id,
                name: $receipt->name,
                category: $firstItem?->category->name ?? 'Ingen kategori',
                itemCount: $receipt->items->count(),
                currency: $receipt->currency,
                amount: $receipt->total,
                date: $this->toImmutable($receipt->date),
            );
        }

        return $summaries;
    }

    private function toImmutable(?\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date ?? \now());
    }
}
