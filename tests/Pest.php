<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
 */

\pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTransactions::class)
    ->in('Feature');

\pest()->extend(Tests\TestCase::class)
    ->in('Unit/Services/Fastmail');

\pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTransactions::class)
    ->in('Unit/Services/Mail');

\pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTransactions::class)
    ->in('Unit/Services/Receipts');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
 */

\expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
 */

/**
 * @param array{
 *     emailQueryIds?: list<string>,
 *     emailList?: list<array<string, mixed>>,
 *     mailboxes?: list<array<string, mixed>>,
 *     n8nOutput?: null|array<string, mixed>,
 *     n8nStatus?: int
 * } $options
 */
function fakeFastmailJmapApi(array $options = []): void
{
    $mailboxes = $options['mailboxes'] ?? [
        [
            'id' => 'mbox-inbox',
            'name' => 'Inbox',
            'role' => 'inbox',
            'totalEmails' => 0,
            'unreadEmails' => 0,
        ],
        [
            'id' => 'mbox-archive',
            'name' => 'Archive',
            'role' => 'archive',
            'totalEmails' => 1,
            'unreadEmails' => 0,
        ],
    ];

    $emailQueryIds = $options['emailQueryIds'] ?? [];
    $emailList = $options['emailList'] ?? [];
    $n8nOutput = $options['n8nOutput'] ?? null;
    $n8nStatus = $options['n8nStatus'] ?? 200;

    Illuminate\Support\Facades\Http::fake(function (Illuminate\Http\Client\Request $request) use ($mailboxes, $emailQueryIds, $emailList, $n8nOutput, $n8nStatus) {
        $webhookUrl = \config('n8n.webhook_url');

        if (
            $n8nOutput !== null
            && \is_string($webhookUrl)
            && $webhookUrl !== ''
            && $request->url() === $webhookUrl
        ) {
            return Illuminate\Support\Facades\Http::response(
                ['output' => $n8nOutput],
                $n8nStatus,
            );
        }

        if (\str_contains($request->url(), '/jmap/session')) {
            return Illuminate\Support\Facades\Http::response([
                'apiUrl' => 'https://api.fastmail.com/jmap/api/',
                'username' => 'user@fastmail.com',
                'accounts' => [
                    'account-abc' => [
                        'name' => 'user@fastmail.com',
                        'isPersonal' => true,
                        'isReadOnly' => false,
                    ],
                ],
                'primaryAccounts' => [
                    'urn:ietf:params:jmap:core' => 'account-abc',
                    'urn:ietf:params:jmap:mail' => 'account-abc',
                ],
            ]);
        }

        if (!\str_contains($request->url(), '/jmap/api')) {
            return Illuminate\Support\Facades\Http::response([], 404);
        }

        $methodCalls = $request->data()['methodCalls'] ?? [];
        $methods = \array_map(
            static fn(array $call): string => (string) ($call[0] ?? ''),
            $methodCalls,
        );

        if (\in_array('Email/query', $methods, true)) {
            $queryArguments = [];

            foreach ($methodCalls as $call) {
                if (($call[0] ?? '') === 'Email/query') {
                    $queryArguments = \is_array($call[1] ?? null) ? $call[1] : [];

                    break;
                }
            }

            $position = \is_int($queryArguments['position'] ?? null) ? $queryArguments['position'] : 0;
            $limit = \is_int($queryArguments['limit'] ?? null) ? $queryArguments['limit'] : 25;
            $pageIds = \array_slice($emailQueryIds, $position, $limit);

            $responses = [
                [
                    'Email/query',
                    [
                        'ids' => $pageIds,
                        'total' => \count($emailQueryIds),
                        'position' => $position,
                    ],
                    'c0',
                ],
            ];

            if (\in_array('Email/get', $methods, true)) {
                $responses[] = [
                    'Email/get',
                    ['list' => $emailList],
                    'c1',
                ];
            }

            return Illuminate\Support\Facades\Http::response([
                'methodResponses' => $responses,
            ]);
        }

        if (\in_array('Email/get', $methods, true)) {
            return Illuminate\Support\Facades\Http::response([
                'methodResponses' => [
                    [
                        'Email/get',
                        ['list' => $emailList],
                        'c0',
                    ],
                ],
            ]);
        }

        if (\in_array('Mailbox/get', $methods, true)) {
            return Illuminate\Support\Facades\Http::response([
                'methodResponses' => [
                    [
                        'Mailbox/get',
                        ['list' => $mailboxes],
                        'c0',
                    ],
                ],
            ]);
        }

        if (\in_array('Blob/get', $methods, true)) {
            return Illuminate\Support\Facades\Http::response([
                'methodResponses' => [
                    [
                        'Blob/get',
                        [
                            'blobs' => [
                                'blob-1' => [
                                    'data' => \base64_encode('pdf-bytes'),
                                    'type' => 'application/pdf',
                                ],
                            ],
                        ],
                        'c0',
                    ],
                ],
            ]);
        }

        return Illuminate\Support\Facades\Http::response(['methodResponses' => []], 400);
    });
}

/**
 * @return array<string, mixed>
 */
function defaultN8nReceiptOutput(): array
{
    return [
        'date' => '2026-05-01',
        'time' => '12:00:00',
        'vendor' => 'Coffee Shop',
        'items' => [
            [
                'description' => 'Latte',
                'quantity' => 1,
                'price' => 50.0,
                'category' => 'groceries',
            ],
        ],
    ];
}

/**
 * @param null|array<string, mixed> $output
 */
function fakeN8nReceiptWebhook(?array $output = null, int $status = 200): void
{
    $webhookUrl = \config('n8n.webhook_url');

    if (!\is_string($webhookUrl) || $webhookUrl === '') {
        \config(['n8n.webhook_url' => 'https://n8n.example.com/webhook/receipt']);
    }
}
