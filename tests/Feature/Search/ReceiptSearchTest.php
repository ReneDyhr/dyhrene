<?php

declare(strict_types=1);

use App\Domain\Search\ReceiptSearch;
use App\Domain\Search\SearchQuery;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;

\covers(ReceiptSearch::class);
\covers(SearchQuery::class);

\it('searches receipts by name for the owning user only', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Receipt::factory()->create(['user_id' => $owner->id, 'name' => 'Bilka']);
    Receipt::factory()->create(['user_id' => $owner->id, 'name' => 'Bauhaus']);
    Receipt::factory()->create(['user_id' => $other->id, 'name' => 'Bilka']);

    $results = (new ReceiptSearch())->searchFor($owner, new SearchQuery('bilka'));

    \expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Bilka');
});

\it('searches receipts by item name', function (): void {
    $owner = User::factory()->create();

    $receipt = Receipt::factory()->create(['user_id' => $owner->id, 'name' => 'Netto']);
    ReceiptItem::factory()->for($receipt)->create(['name' => 'Mælk']);

    $results = (new ReceiptSearch())->searchFor($owner, new SearchQuery('mælk'));

    \expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Netto');
});

\it('returns no results for an empty or whitespace-only query', function (): void {
    $owner = User::factory()->create();

    Receipt::factory()->create(['user_id' => $owner->id, 'name' => 'Bilka']);

    \expect((new ReceiptSearch())->searchFor($owner, new SearchQuery('   ')))->toBe([])
        ->and((new ReceiptSearch())->searchFor($owner, new SearchQuery('')))->toBe([]);
});
