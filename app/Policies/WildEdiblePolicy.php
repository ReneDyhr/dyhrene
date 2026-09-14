<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WildEdible;

class WildEdiblePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, WildEdible $wildEdible): bool
    {
        return $wildEdible->user_id === $user->id && !$wildEdible->trashed();
    }

    public function update(User $user, WildEdible $wildEdible): bool
    {
        return $wildEdible->user_id === $user->id && !$wildEdible->trashed();
    }

    public function delete(User $user, WildEdible $wildEdible): bool
    {
        return $wildEdible->user_id === $user->id && !$wildEdible->trashed();
    }

    public function restore(User $user, WildEdible $wildEdible): bool
    {
        return $wildEdible->user_id === $user->id && $wildEdible->trashed();
    }

    public function forceDelete(User $user, WildEdible $wildEdible): bool
    {
        return $wildEdible->user_id === $user->id && $wildEdible->trashed();
    }
}
