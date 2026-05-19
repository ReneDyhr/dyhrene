<?php

declare(strict_types=1);

namespace App\Services\Fastmail;

use App\Services\Fastmail\DTOs\EmailIdentity;
use App\Services\Fastmail\Exceptions\FastmailConfigurationException;
use Illuminate\Support\Collection;

class FastmailIdentityService
{
    public function __construct(
        private readonly FastmailJmapClient $client,
    ) {}

    /**
     * Sending identities on the account (one per domain / alias).
     *
     * @return Collection<int, EmailIdentity>
     */
    public function listIdentities(): Collection
    {
        $result = $this->client->call('Identity/get', [
            'ids' => null,
            'properties' => ['id', 'email', 'name'],
        ]);

        /** @var list<array<string, mixed>> $list */
        $list = $result['list'] ?? [];

        return \collect($list)
            ->map(static fn(array $data): EmailIdentity => EmailIdentity::fromJmap($data))
            ->sortBy(static fn(EmailIdentity $identity): string => $identity->email)
            ->values();
    }

    public function configuredRecipient(): string
    {
        $email = \config('fastmail.email');

        if (!\is_string($email) || $email === '') {
            throw new FastmailConfigurationException(
                'FASTMAIL_EMAIL is not configured.',
            );
        }

        return $email;
    }
}
