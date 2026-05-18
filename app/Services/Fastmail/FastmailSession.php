<?php

declare(strict_types=1);

namespace App\Services\Fastmail;

final readonly class FastmailSession
{
    public function __construct(
        public string $accountId,
        public string $apiUrl,
        public string $email,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountId: (string) $data['accountId'],
            apiUrl: (string) $data['apiUrl'],
            email: (string) $data['email'],
        );
    }

    /**
     * @return array{accountId: string, apiUrl: string, email: string}
     */
    public function toArray(): array
    {
        return [
            'accountId' => $this->accountId,
            'apiUrl' => $this->apiUrl,
            'email' => $this->email,
        ];
    }
}
