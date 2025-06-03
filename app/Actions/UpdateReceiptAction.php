<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Receipt;

class UpdateReceiptAction
{
    /**
     * @param array{name: string, vendor?: string, description?: string, currency: string, total: float, date: string, file_path?: string} $data
     */
    public function handle(Receipt $receipt, array $data): Receipt
    {
        $receipt->update($data);

        return $receipt;
    }
}
