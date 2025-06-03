<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Receipt;
use App\Models\User;

class CreateReceiptAction
{
    /**
     * @param array{name: string, vendor?: string, description?: string, currency: string, total: float, date: string, file_path?: string} $data
     */
    public function handle(User $user, array $data): Receipt
    {
        $data['user_id'] = $user->id;

        return Receipt::query()->create($data);
    }
}
