<?php

declare(strict_types=1);

namespace App\Support\Sentry;

use Sentry\Event;

final class ScrubSensitiveWildEdibleEvent
{
    public function __invoke(Event $event): Event
    {
        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, $this->scrub($context));
        }

        $event->setExtra($this->scrub($event->getExtra()));
        $event->setRequest($this->scrub($event->getRequest()));

        return $event;
    }

    /**
     * @param  array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function scrub(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $values[$key] = '[REDACTED]';

                continue;
            }

            $values[$key] = $this->scrubValue($value);
        }

        return $values;
    }

    private function scrubValue(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $nestedValue) {
            if ($this->isSensitiveKey((string) $key)) {
                $value[$key] = '[REDACTED]';

                continue;
            }

            $value[$key] = $this->scrubValue($nestedValue);
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return \in_array(\strtolower($key), ['latitude', 'longitude', 'lat', 'lng'], true);
    }
}
