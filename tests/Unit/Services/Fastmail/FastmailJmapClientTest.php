<?php

declare(strict_types=1);

use App\Services\Fastmail\Exceptions\FastmailConfigurationException;
use App\Services\Fastmail\FastmailJmapClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

\beforeEach(function () {
    Cache::flush();
    \app()->forgetInstance(FastmailJmapClient::class);
    \config([
        'fastmail.token' => 'test-token',
        'fastmail.email' => 'user@fastmail.com',
        'fastmail.session_url' => 'https://api.fastmail.com/jmap/session',
        'fastmail.session_cache_ttl' => 3600,
    ]);
});

\it('resolves session account id from configured email', function () {
    Http::fake([
        'api.fastmail.com/jmap/session' => Http::response([
            'apiUrl' => 'https://api.fastmail.com/jmap/api/',
            'primaryAccounts' => [
                'user@fastmail.com' => 'account-abc',
            ],
        ]),
        'api.fastmail.com/jmap/api/*' => Http::response([
            'methodResponses' => [
                [
                    'Mailbox/get',
                    [
                        'list' => [],
                        'state' => 's1',
                    ],
                    'c0',
                ],
            ],
        ]),
    ]);

    $client = new FastmailJmapClient();
    $session = $client->resolveSession();

    \expect($session->accountId)->toBe('account-abc')
        ->and($session->apiUrl)->toBe('https://api.fastmail.com/jmap/api/')
        ->and($session->email)->toBe('user@fastmail.com');

    $result = $client->call('Mailbox/get', ['ids' => null]);

    \expect($result)->toHaveKey('list');

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://api.fastmail.com/jmap/api/') {
            return false;
        }

        $body = $request->data();

        return ($body['using'] ?? []) === [
            'urn:ietf:params:jmap:core',
            'urn:ietf:params:jmap:mail',
        ]
            && ($body['methodCalls'][0][0] ?? null) === 'Mailbox/get'
            && ($body['methodCalls'][0][1]['accountId'] ?? null) === 'account-abc';
    });
});

\it('throws when configured email is not on the token', function () {
    Http::fake([
        'api.fastmail.com/jmap/session' => Http::response([
            'apiUrl' => 'https://api.fastmail.com/jmap/api/',
            'primaryAccounts' => [
                'other@fastmail.com' => 'account-xyz',
            ],
        ]),
    ]);

    $client = new FastmailJmapClient();

    \expect(fn() => $client->resolveSession())
        ->toThrow(FastmailConfigurationException::class);
});

\it('throws when api token is missing', function () {
    \config(['fastmail.token' => '']);

    $client = new FastmailJmapClient();

    \expect(fn() => $client->resolveSession())
        ->toThrow(FastmailConfigurationException::class);
});
