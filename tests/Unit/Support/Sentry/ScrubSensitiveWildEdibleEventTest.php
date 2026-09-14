<?php

declare(strict_types=1);

use App\Support\Sentry\ScrubSensitiveWildEdibleEvent;
use Sentry\Event;

\covers(ScrubSensitiveWildEdibleEvent::class);

\test('it scrubs exact coordinates from Sentry event payloads', function (): void {
    $event = Event::createEvent()
        ->setContext('request', ['latitude' => 55.4, 'nested' => ['longitude' => 9.1]])
        ->setExtra(['lat' => 55.4, 'safe' => 'value'])
        ->setRequest(['data' => ['lng' => 9.1]]);

    $scrubbed = (new ScrubSensitiveWildEdibleEvent())($event);

    \expect($scrubbed->getContexts()['request'])->toMatchArray([
        'latitude' => '[REDACTED]',
        'nested' => ['longitude' => '[REDACTED]'],
    ])
        ->and($scrubbed->getExtra())->toMatchArray(['lat' => '[REDACTED]', 'safe' => 'value'])
        ->and($scrubbed->getRequest())->toMatchArray(['data' => ['lng' => '[REDACTED]']]);
});
