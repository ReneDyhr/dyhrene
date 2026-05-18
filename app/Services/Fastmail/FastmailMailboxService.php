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
        return $this->listMailboxes()->first(
            static fn(Mailbox $mailbox): bool => $mailbox->role === 'inbox',
        );
    }
}
