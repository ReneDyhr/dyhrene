<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Receipt;

class UpdateReceiptAction
{
    /**
     * @param array{name?: null|string, vendor?: null|string, description?: null|string, currency?: null|string, date?: null|string, file_path?: null|string} $data
     */
    public function handle(Receipt $receipt, array $data): Receipt
    {
        $receipt->update($data);

        return $receipt;
    }
}
