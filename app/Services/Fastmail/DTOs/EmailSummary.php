<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

use App\Services\Fastmail\Support\JmapCasts;
use Carbon\CarbonImmutable;

final readonly class EmailSummary
{
    /**
     * @param list<array{name: ?string, email: ?string}> $from
     * @param list<string>                               $mailboxIds
     */
    public function __construct(
        public string $id,
        public string $subject,
        public array $from,
        public ?CarbonImmutable $receivedAt,
        public ?string $preview,
        public bool $hasAttachment,
        public array $mailboxIds,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromJmap(array $data): self
    {
        $receivedAt = null;

        if (isset($data['receivedAt']) && \is_string($data['receivedAt'])) {
            $receivedAt = CarbonImmutable::parse($data['receivedAt']);
        }

        /** @var list<array{name: ?string, email: ?string}> $from */
        $from = [];

        if (isset($data['from']) && \is_array($data['from'])) {
            foreach ($data['from'] as $entry) {
                if (!\is_array($entry)) {
                    continue;
                }
                $from[] = [
                    'name' => JmapCasts::nullableString($entry['name'] ?? null),
                    'email' => JmapCasts::nullableString($entry['email'] ?? null),
                ];
            }
        }

        /** @var list<string> $mailboxIds */
        $mailboxIds = [];

        if (isset($data['mailboxIds']) && \is_array($data['mailboxIds'])) {
            foreach ($data['mailboxIds'] as $mailboxId) {
                if (\is_string($mailboxId)) {
                    $mailboxIds[] = $mailboxId;
                }
            }
        }

        return new self(
            id: JmapCasts::string($data['id'] ?? null),
            subject: JmapCasts::string($data['subject'] ?? null),
            from: $from,
            receivedAt: $receivedAt,
            preview: JmapCasts::nullableString($data['preview'] ?? null),
            hasAttachment: (bool) ($data['hasAttachment'] ?? false),
            mailboxIds: $mailboxIds,
        );
    }

    public function fromDisplay(): string
    {
        if ($this->from === []) {
            return '';
        }

        $first = $this->from[0];
        $name = $first['name'] ?? '';
        $email = $first['email'] ?? '';

        if ($name !== '' && $email !== '') {
            return $name . ' <' . $email . '>';
        }

        return $email !== '' ? $email : $name;
    }
}
