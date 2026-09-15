<?php

declare(strict_types=1);

namespace App\Services\Overview;

use App\Domain\Overview\Contracts\OverviewSnapshotReaderInterface;
use App\Domain\Overview\InventoryOverview;
use App\Domain\Overview\LatestReceipt;
use App\Domain\Overview\LatestRecipe;
use App\Domain\Overview\ObservationOverview;
use App\Domain\Overview\OverviewSnapshot;
use App\Domain\Overview\ReceiptOverview;
use App\Domain\Overview\RecipeOverview;
use App\Domain\Overview\ShoppingListOverview;
use App\Domain\Overview\WildEdibleOverview;
use App\Models\InventoryItem;
use App\Models\Observation;
use App\Models\Receipt;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\Species;
use App\Models\WildEdible;
use Illuminate\Database\Eloquent\Builder;

final class EloquentOverviewSnapshotReader implements OverviewSnapshotReaderInterface
{
    public function read(int $userId, \DateTimeImmutable $now): OverviewSnapshot
    {
        $monthStart = $now->setTime(0, 0)->modify('first day of this month');
        $nextMonthStart = $monthStart->modify('+1 month');
        $latestRecipe = Recipe::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
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

        return new OverviewSnapshot(
            recipes: new RecipeOverview(
                count: Recipe::query()->where('user_id', $userId)->count(),
                latest: $latestRecipe instanceof Recipe ? new LatestRecipe(
                    id: $latestRecipe->id,
                    name: $latestRecipe->name,
                    createdAt: $this->immutableDate($latestRecipe->created_at),
                ) : null,
            ),
            shoppingList: new ShoppingListOverview(
                activeActionableCount: ShoppingList::query()
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where('name', 'not like', '#%')
                    ->count(),
            ),
            receipts: new ReceiptOverview(
                currentMonthCount: $currentMonthReceipts->count(),
                latest: $latestReceipt instanceof Receipt ? new LatestReceipt(
                    id: $latestReceipt->id,
                    name: $latestReceipt->name,
                    vendor: $latestReceipt->vendor,
                    currency: $latestReceipt->currency,
                    date: $this->immutableDate($latestReceipt->date),
                    amount: $latestReceipt->total,
                ) : null,
                amountsByCurrency: $amountsByCurrency,
            ),
            inventory: new InventoryOverview(
                count: InventoryItem::query()->where('user_id', $userId)->count(),
            ),
            observations: new ObservationOverview(
                count: Observation::query()
                    ->where('user_id', $userId)
                    ->whereHas('species', static function (Builder $query) use ($userId): void {
                        /** @var Builder<Species> $query */
                        $query->where('user_id', $userId)->where('status', '!=', 'rejected');
                    })
                    ->count(),
            ),
            wildEdibles: new WildEdibleOverview(
                count: WildEdible::query()->where('user_id', $userId)->count(),
            ),
        );
    }

    private function immutableDate(?\DateTimeInterface $date): \DateTimeImmutable
    {
        if ($date === null) {
            throw new \LogicException('Overview dates must be present.');
        }

        return \DateTimeImmutable::createFromInterface($date);
    }
}
