<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, \Closure $next, ...$guards): mixed
    {
        if (\auth()->guest()) {
            if ($request->expectsJson() || \str_starts_with($request->path(), 'api/')) {
                return \response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return \redirect()->route('login');
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            return \route('login');
        }

        return null;
    }
}
