<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ReceiptSearch
{
    /**
     * Search the user's receipts by name, vendor, description, or item name.
     * Owns normalization, ownership scoping, ordering, and empty-query behaviour.
     *
     * @return list<Receipt>
     */
    public function searchFor(User $actor, SearchQuery $query): array
    {
        if ($query->isEmpty()) {
            return [];
        }

        $term = $query->normalized();

        return \array_values(
            Receipt::query()
                ->with(['items.category'])
                ->where('user_id', $actor->id)
                ->where(function (Builder $q) use ($term): void {
                    $q->where('name', 'like', '%' . $term . '%')
                        ->orWhere('vendor', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%')
                        ->orWhereHas('items', function (Builder $iq) use ($term): void {
                            $iq->where('name', 'like', '%' . $term . '%');
                        });
                })
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get()
                ->all(),
        );
    }
}
