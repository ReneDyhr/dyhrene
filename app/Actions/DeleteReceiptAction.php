<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\MailMessageClassification;
use App\Models\Receipt;

class DeleteReceiptAction
{
    public function handle(Receipt $receipt): void
    {
        $receipt->delete();

        MailMessageClassification::query()
            ->where('receipt_id', $receipt->id)
            ->update(['receipt_id' => null, 'processed_at' => null]);
    }
}
