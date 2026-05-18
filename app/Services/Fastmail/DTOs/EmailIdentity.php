<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

final readonly class EmailIdentity
{
    public function __construct(
        public string $id,
        public string $email,
        public ?string $name,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromJmap(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            name: isset($data['name']) ? (string) $data['name'] : null,
        );
    }
}
