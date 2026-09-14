<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Picking;
use App\Models\User;

class PickingPolicy
{
    public function view(User $user, Picking $picking): bool
    {
        return $picking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Picking $picking): bool
    {
        return $picking->user_id === $user->id;
    }

    public function delete(User $user, Picking $picking): bool
    {
        return $picking->user_id === $user->id;
    }
}
