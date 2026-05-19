<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Fastmail JMAP credentials
    |--------------------------------------------------------------------------
    |
    | API token from Fastmail Settings → Privacy & Security → Manage API tokens.
    | Email must match an account in primaryAccounts from the JMAP session.
    |
     */

    'token' => \env('FASTMAIL_API_TOKEN'),

    'email' => \env('FASTMAIL_EMAIL'),

    /*
    | When true, email queries only return messages To/Cc the FASTMAIL_EMAIL address.
    | Disable to show all mail in the selected mailbox (every domain on the account).
     */
    'filter_to_recipient' => \env('FASTMAIL_FILTER_TO_RECIPIENT', true),

    // | JMAP mailbox role to open by default in the Mail UI (e.g. archive, inbox).
    'default_mailbox_role' => \env('FASTMAIL_DEFAULT_MAILBOX_ROLE', 'archive'),

    'session_url' => \env('FASTMAIL_SESSION_URL', 'https://api.fastmail.com/jmap/session'),

    'session_cache_ttl' => (int) \env('FASTMAIL_SESSION_CACHE_TTL', 3600),
];
