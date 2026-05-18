<?php

declare(strict_types=1);

use App\Services\Fastmail\EmailQuery;

\it('scopes queries to recipient to and cc addresses', function () {
    $query = (new EmailQuery())
        ->scopedToRecipient('forward@example.com')
        ->inMailbox('mbox-1');

    \expect($query->toFilter())->toBe([
        'operator' => 'AND',
        'conditions' => [
            [
                'operator' => 'OR',
                'conditions' => [
                    ['to' => 'forward@example.com'],
                    ['cc' => 'forward@example.com'],
                ],
            ],
            ['inMailbox' => 'mbox-1'],
        ],
    ]);
});

\it('uses only recipient filter when no other criteria are set', function () {
    $query = (new EmailQuery())->scopedToRecipient('user@test.com');

    \expect($query->toFilter())->toBe([
        'operator' => 'OR',
        'conditions' => [
            ['to' => 'user@test.com'],
            ['cc' => 'user@test.com'],
        ],
    ]);
});
