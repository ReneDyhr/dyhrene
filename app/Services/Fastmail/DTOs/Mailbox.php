<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

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
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            role: isset($data['role']) ? (string) $data['role'] : null,
            totalEmails: (int) ($data['totalEmails'] ?? 0),
            unreadEmails: (int) ($data['unreadEmails'] ?? 0),
        );
    }
}
