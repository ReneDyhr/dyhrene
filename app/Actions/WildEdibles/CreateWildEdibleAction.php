<?php

declare(strict_types=1);

namespace App\Actions\WildEdibles;

use App\Models\User;
use App\Models\WildEdible;

final class CreateWildEdibleAction
{
    /** @param array<string, mixed> $attributes */
    public function handle(User $user, array $attributes): WildEdible
    {
        $attributes['user_id'] = $user->id;

        return WildEdible::query()->create($attributes);
    }
}
