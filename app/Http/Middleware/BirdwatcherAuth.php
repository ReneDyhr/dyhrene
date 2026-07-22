<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BirdwatcherAuth
{
    public function handle(Request $request, \Closure $next): Response
    {
        // When auth is disabled, pre-authenticate the hardcoded user.
        // The auth:api middleware runs after this and sees the user is set.
        if (!(bool) \config('birdwatcher.auth_enabled', true)) {
            $email = \config('birdwatcher.hardcoded_user_email');

            if (!\is_string($email) || $email === '') {
                \abort(500, 'BIRDWATCHER_HARDCODED_USER_EMAIL not configured.');
            }

            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                \abort(500, "Hardcoded user '{$email}' not found.");
            }

            \auth()->guard('api')->setUser($user);
        }

        return $next($request);
    }
}
