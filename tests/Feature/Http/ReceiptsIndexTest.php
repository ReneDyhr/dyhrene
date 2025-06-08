<?php

declare(strict_types=1);

use App\Livewire\Receipts\Index;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

\uses(RefreshDatabase::class);

\describe('Receipts Livewire Index', function () {
    \it('can soft delete a receipt via Livewire', function () {
        $user = User::factory()->create();
        $receipt = Receipt::factory()->for($user)->create();
        \expect($receipt->deleted_at)->toBeNull();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('deleteReceipt', $receipt->id);

        $receipt->refresh();
        \expect($receipt->deleted_at)->not()->toBeNull();
    });
});
