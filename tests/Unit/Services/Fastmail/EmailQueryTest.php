<?php

declare(strict_types=1);

use App\Services\Fastmail\EmailQuery;
use Carbon\Carbon;

\it('builds a JMAP filter from query options', function () {
    $query = (new EmailQuery())
        ->inMailbox('mbox-inbox')
        ->from('shop@example.com')
        ->subject('receipt')
        ->receivedAfter(Carbon::parse('2026-01-01'))
        ->hasAttachment(true)
        ->text('invoice')
        ->limit(10)
        ->position(5);

    \expect($query->toFilter())->toMatchArray([
        'inMailbox' => 'mbox-inbox',
        'from' => 'shop@example.com',
        'subject' => 'receipt',
        'hasAttachment' => true,
        'text' => 'invoice',
    ]);
    \expect($query->toFilter()['after'] ?? null)->toStartWith('2026-01-01');

    \expect($query->toArguments())->toMatchArray([
        'limit' => 10,
        'position' => 5,
        'filter' => $query->toFilter(),
    ]);
});

\it('omits an empty filter from query arguments', function () {
    $query = (new EmailQuery())->limit(25);

    \expect($query->toFilter())->toBe([]);
    \expect($query->toArguments())->not->toHaveKey('filter');
});
