<?php

declare(strict_types=1);

namespace App\Actions\WildEdibles;

use App\Models\Picking;
use App\Models\User;
use App\Models\WildEdible;

final class CreatePickingAction
{
    /** @param array<string, mixed> $attributes */
    public function handle(User $user, WildEdible $wildEdible, array $attributes): Picking
    {
        if ($wildEdible->user_id !== $user->id || $wildEdible->trashed()) {
            throw new \Illuminate\Auth\Access\AuthorizationException();
        }

        $attributes['user_id'] = $user->id;
        $attributes['wild_edible_id'] = $wildEdible->id;

        return Picking::query()->create($attributes);
    }
}
