<?php

declare(strict_types=1);

namespace App\Services\Fastmail;

use App\Services\Fastmail\DTOs\Mailbox;
use Illuminate\Support\Collection;

class FastmailMailboxService
{
    public function __construct(
        private readonly FastmailJmapClient $client,
    ) {}

    /**
     * @return Collection<int, Mailbox>
     */
    public function listMailboxes(): Collection
    {
        $result = $this->client->call('Mailbox/get', [
            'ids' => null,
            'properties' => ['id', 'name', 'role', 'totalEmails', 'unreadEmails'],
        ]);

        /** @var list<array<string, mixed>> $list */
        $list = $result['list'] ?? [];

        return \collect($list)->map(static fn(array $data): Mailbox => Mailbox::fromJmap($data));
    }

    public function findInbox(): ?Mailbox
    {
        return $this->findByRole('inbox');
    }

    public function findDefaultMailbox(): ?Mailbox
    {
        $roleConfig = \config('fastmail.default_mailbox_role', 'archive');
        $preferredRole = \is_string($roleConfig) && $roleConfig !== '' ? $roleConfig : 'archive';

        $preferred = $this->findByRole($preferredRole);

        if ($preferred !== null) {
            return $preferred;
        }

        $inbox = $this->findByRole('inbox');

        if ($inbox !== null) {
            return $inbox;
        }

        return $this->listMailboxes()->first();
    }

    public function findByRole(string $role): ?Mailbox
    {
        return $this->listMailboxes()->first(
            static fn(Mailbox $mailbox): bool => $mailbox->role === $role,
        );
    }
}
