<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WildEdiblePhoto;

class WildEdiblePhotoPolicy
{
    public function view(User $user, WildEdiblePhoto $photo): bool
    {
        return $photo->wildEdible?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, WildEdiblePhoto $photo): bool
    {
        return $photo->wildEdible?->user_id === $user->id;
    }
}
