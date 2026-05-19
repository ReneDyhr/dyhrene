<?php

declare(strict_types=1);

namespace App\Services\Fastmail;

use App\Services\Fastmail\Support\JmapCasts;

final readonly class FastmailSession
{
    public function __construct(
        public string $accountId,
        public string $apiUrl,
        public string $email,
        public string $downloadUrl = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountId: JmapCasts::string($data['accountId'] ?? null),
            apiUrl: JmapCasts::string($data['apiUrl'] ?? null),
            email: JmapCasts::string($data['email'] ?? null),
            downloadUrl: JmapCasts::string($data['downloadUrl'] ?? null),
        );
    }

    /**
     * @return array{accountId: string, apiUrl: string, email: string, downloadUrl: string}
     */
    public function toArray(): array
    {
        return [
            'accountId' => $this->accountId,
            'apiUrl' => $this->apiUrl,
            'email' => $this->email,
            'downloadUrl' => $this->downloadUrl,
        ];
    }
}
