<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Birdwatcher Authentication
    |--------------------------------------------------------------------------
    |
    | When enabled (default), requests must include a valid Passport Bearer token.
    | Disable for development to use a hardcoded user instead.
    |
     */
    'auth_enabled' => \env('BIRDWATCHER_AUTH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Hardcoded User
    |--------------------------------------------------------------------------
    |
    | When auth_enabled is false, all requests are attributed to this user
    | by email address. Must reference an existing user in the database.
    |
     */
    'hardcoded_user_email' => \env('BIRDWATCHER_HARDCODED_USER_EMAIL'),
];
