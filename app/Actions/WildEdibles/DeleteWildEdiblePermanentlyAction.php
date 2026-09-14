<?php

declare(strict_types=1);

namespace App\Actions\WildEdibles;

use App\Models\User;
use App\Models\WildEdible;
use Illuminate\Auth\Access\AuthorizationException;

final class DeleteWildEdiblePermanentlyAction
{
    public function handle(User $user, WildEdible $wildEdible): void
    {
        if ($wildEdible->user_id !== $user->id || !$wildEdible->trashed()) {
            throw new AuthorizationException();
        }

        $wildEdible->forceDelete();
    }
}
