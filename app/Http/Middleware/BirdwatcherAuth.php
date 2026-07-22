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
        if (!\config('birdwatcher.auth_enabled', true)) {
            // Auth disabled — resolve hardcoded user
            $email = \config('birdwatcher.hardcoded_user_email');

            if (!\is_string($email) || $email === '') {
                \abort(500, 'BIRDWATCHER_HARDCODED_USER_EMAIL not configured.');
            }

            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                \abort(500, "Hardcoded user '{$email}' not found.");
            }

            \auth()->setUser($user);

            return $next($request);
        }

        // Auth enabled — delegates to Passport auth:api guard
        \auth('api')->authenticate();

        return $next($request);
    }
}
