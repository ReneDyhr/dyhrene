<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Receipt;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class ReceiptDuplicateGuard
{
    /**
     * Whether another receipt for this user already matches vendor, exact datetime, and line-item total.
     */
    public static function duplicateExists(User $user, ?string $vendor, CarbonInterface $receiptDate, float $itemsTotal): bool
    {
        $existingReceipts = Receipt::query()
            ->where('user_id', $user->id)
            ->where('date', $receiptDate->format('Y-m-d H:i:s'))
            ->where(function (Builder $query) use ($vendor): void {
                if ($vendor === null || $vendor === '') {
                    $query->whereNull('vendor');
                } else {
                    $query->where('vendor', $vendor);
                }
            })
            ->with('items')
            ->get();

        foreach ($existingReceipts as $existingReceipt) {
            $existingTotal = 0.0;

            foreach ($existingReceipt->items as $item) {
                $existingTotal += $item->amount * $item->quantity;
            }

            if (\abs($itemsTotal - $existingTotal) < 0.01) {
                return true;
            }
        }

        return false;
    }
}
