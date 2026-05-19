<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

use App\Services\Fastmail\Support\JmapCasts;

final readonly class Mailbox
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $role,
        public int $totalEmails,
        public int $unreadEmails,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromJmap(array $data): self
    {
        return new self(
            id: JmapCasts::string($data['id'] ?? null),
            name: JmapCasts::string($data['name'] ?? null),
            role: JmapCasts::nullableString($data['role'] ?? null),
            totalEmails: JmapCasts::int($data['totalEmails'] ?? null),
            unreadEmails: JmapCasts::int($data['unreadEmails'] ?? null),
        );
    }
}
