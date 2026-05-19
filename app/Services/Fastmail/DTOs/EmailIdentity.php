<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

use App\Services\Fastmail\Support\JmapCasts;

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
            id: JmapCasts::string($data['id'] ?? null),
            email: JmapCasts::string($data['email'] ?? null),
            name: JmapCasts::nullableString($data['name'] ?? null),
        );
    }
}
