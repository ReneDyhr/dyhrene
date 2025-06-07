<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Receipt;
use App\Models\User;

class CreateReceiptAction
{
    /**
     * @param ?array{name?: null|string, vendor?: null|string, description?: null|string, currency?: null|string, date?: null|string, file_path?: null|string} $data
     */
    public function handle(User $user, ?array $data): Receipt
    {
        $data['user_id'] = $user->id;

        return Receipt::query()->create($data);
    }
}
