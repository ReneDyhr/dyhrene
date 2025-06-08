<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Receipt;

class DeleteReceiptAction
{
    public function handle(Receipt $receipt): void
    {
        $receipt->delete();
    }
}
