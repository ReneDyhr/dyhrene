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
        if (!(bool) \config('birdwatcher.auth_enabled', true)) {
            // Auth disabled — pre-authenticate hardcoded user, skip Passport
            $email = \config('birdwatcher.hardcoded_user_email');

            if (!\is_string($email) || $email === '') {
                return \response()->json([
                    'message' => 'BIRDWATCHER_HARDCODED_USER_EMAIL not configured.',
                ], 500);
            }

            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                return \response()->json([
                    'message' => "Hardcoded user '{$email}' not found.",
                ], 500);
            }

            \auth()->guard('api')->setUser($user);

            return $next($request);
        }

        // Auth enabled — validate Bearer token via Passport
        if (!\auth('api')->check()) {
            return \response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
