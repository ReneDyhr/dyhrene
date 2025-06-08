<?php

declare(strict_types=1);

use App\Actions\DeleteReceiptAction;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

\uses(RefreshDatabase::class);

\describe('Receipt Deletion', function () {
    \it('soft deletes a receipt', function () {
        $user = User::factory()->create();
        $receipt = Receipt::factory()->for($user)->create();
        \expect($receipt->deleted_at)->toBeNull();

        $action = \app(DeleteReceiptAction::class);
        $action->handle($receipt);
        $receipt->refresh();

        \expect($receipt->deleted_at)->not()->toBeNull();
        \expect(Receipt::query()->find($receipt->id))->toBeNull();
        \expect(Receipt::withTrashed()->find($receipt->id))->not()->toBeNull();
    });
});
