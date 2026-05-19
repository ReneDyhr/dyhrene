<?php

declare(strict_types=1);

namespace App\Services\Fastmail;

use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\DTOs\EmailQueryResult;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Fastmail\Support\JmapCasts;
use Illuminate\Support\Collection;

class FastmailEmailService
{
    private const SUMMARY_PROPERTIES = [
        'id',
        'subject',
        'from',
        'receivedAt',
        'preview',
        'hasAttachment',
        'mailboxIds',
    ];

    private const MESSAGE_PROPERTIES = [
        'id',
        'subject',
        'from',
        'to',
        'receivedAt',
        'preview',
        'hasAttachment',
        'mailboxIds',
        'bodyStructure',
        'bodyValues',
    ];

    public function __construct(
        private readonly FastmailJmapClient $client,
    ) {}

    /**
     * Base query scoped to FASTMAIL_EMAIL when {@see config()} fastmail.filter_to_recipient is true.
     */
    public function defaultQuery(): EmailQuery
    {
        $query = new EmailQuery();

        if (\config('fastmail.filter_to_recipient', true) !== true) {
            return $query;
        }

        return $query->scopedToConfiguredRecipient();
    }

    public function query(EmailQuery $query): EmailQueryResult
    {
        $result = $this->client->call('Email/query', $query->toArguments());

        /** @var list<string> $ids */
        $ids = [];

        if (isset($result['ids']) && \is_array($result['ids'])) {
            foreach ($result['ids'] as $id) {
                if (\is_string($id)) {
                    $ids[] = $id;
                }
            }
        }

        return new EmailQueryResult(
            ids: $ids,
            total: JmapCasts::int($result['total'] ?? null),
            position: JmapCasts::int($result['position'] ?? null, $query->getPosition()),
            queryState: JmapCasts::nullableString($result['queryState'] ?? null),
        );
    }

    /**
     * @param list<string> $ids
     *
     * @return Collection<int, EmailSummary>
     */
    public function getSummaries(array $ids): Collection
    {
        if ($ids === []) {
            return \collect();
        }

        $result = $this->client->call('Email/get', [
            'ids' => $ids,
            'properties' => self::SUMMARY_PROPERTIES,
        ]);

        return $this->mapSummariesFromGetResult($result, $ids);
    }

    /**
     * @return array{result: EmailQueryResult, summaries: Collection<int, EmailSummary>}
     */
    public function search(EmailQuery $query): array
    {
        $emailQueryResult = $this->query($query);

        if ($emailQueryResult->ids === []) {
            return [
                'result' => $emailQueryResult,
                'summaries' => \collect(),
            ];
        }

        return [
            'result' => $emailQueryResult,
            'summaries' => $this->getSummaries($emailQueryResult->ids),
        ];
    }

    public function getMessage(string $id): EmailMessage
    {
        $result = $this->client->call('Email/get', [
            'ids' => [$id],
            'properties' => self::MESSAGE_PROPERTIES,
            'fetchTextBodyValues' => true,
            'fetchHTMLBodyValues' => false,
        ]);

        /** @var list<array<string, mixed>> $list */
        $list = $result['list'] ?? [];

        if ($list === []) {
            throw new \InvalidArgumentException('Email not found: ' . $id);
        }

        /** @var array<string, mixed> $data */
        $data = $list[0];

        $summary = EmailSummary::fromJmap($data);
        $textBody = $this->extractTextBody($data);
        $attachments = $this->extractAttachments($data);

        return new EmailMessage(
            summary: $summary,
            from: $summary->from,
            to: $this->parseAddresses($data['to'] ?? []),
            textBody: $textBody,
            htmlBody: null,
            attachments: $attachments,
        );
    }

    /**
     * @return Collection<int, EmailAttachment>
     */
    public function getAttachments(string $emailId): Collection
    {
        $result = $this->client->call('Email/get', [
            'ids' => [$emailId],
            'properties' => ['id', 'bodyStructure'],
        ]);

        /** @var list<array<string, mixed>> $list */
        $list = $result['list'] ?? [];

        if ($list === []) {
            return \collect();
        }

        return $this->extractAttachments($list[0]);
    }

    public function downloadBlob(
        string $blobId,
        string $filename = 'attachment',
        string $mimeType = 'application/octet-stream',
    ): string {
        $session = $this->client->resolveSession();

        if ($session->downloadUrl !== '') {
            return $this->client->downloadBlob($blobId, $filename, $mimeType);
        }

        return $this->downloadBlobViaJmap($blobId);
    }

    private function downloadBlobViaJmap(string $blobId): string
    {
        $result = $this->client->call('Blob/get', [
            'ids' => [$blobId],
        ]);

        /** @var array<string, array{data?: array<int, string>|string, type?: string}> $blobs */
        $blobs = $result['blobs'] ?? [];

        $blob = $blobs[$blobId] ?? null;

        if (!\is_array($blob)) {
            throw new \RuntimeException('Blob not found: ' . $blobId);
        }

        $data = $blob['data'] ?? '';

        if (\is_array($data)) {
            $encoded = \implode('', $data);
        } else {
            $encoded = JmapCasts::string($data);
        }

        if ($encoded === '') {
            return '';
        }

        $decoded = \base64_decode(\strtr($encoded, '-_', '+/'), true);

        return $decoded !== false ? $decoded : $encoded;
    }

    /**
     * @param array<string, mixed> $result
     * @param list<string>         $order
     *
     * @return Collection<int, EmailSummary>
     */
    private function mapSummariesFromGetResult(array $result, array $order): Collection
    {
        /** @var array<string, array<string, mixed>> $byId */
        $byId = [];

        if (isset($result['list']) && \is_array($result['list'])) {
            foreach ($result['list'] as $item) {
                if (\is_array($item) && isset($item['id']) && \is_string($item['id'])) {
                    $byId[$item['id']] = $item;
                }
            }
        }

        $summaries = [];

        foreach ($order as $id) {
            if (isset($byId[$id])) {
                $summaries[] = EmailSummary::fromJmap($byId[$id]);
            }
        }

        return \collect($summaries);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractTextBody(array $data): ?string
    {
        if (!isset($data['bodyValues']) || !\is_array($data['bodyValues'])) {
            return null;
        }

        foreach ($data['bodyValues'] as $bodyValue) {
            if (!\is_array($bodyValue)) {
                continue;
            }

            $value = $bodyValue['value'] ?? null;

            if (\is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return Collection<int, EmailAttachment>
     */
    private function extractAttachments(array $data): Collection
    {
        if (!isset($data['bodyStructure']) || !\is_array($data['bodyStructure'])) {
            return \collect();
        }

        $rootPart = $this->asStructurePart($data['bodyStructure']);

        if ($rootPart === null) {
            return \collect();
        }

        $attachments = [];
        $this->walkBodyStructure($rootPart, $attachments);

        return \collect($attachments);
    }

    /**
     * @param array<string, mixed>  $part
     * @param list<EmailAttachment> $attachments
     */
    private function walkBodyStructure(array $part, array &$attachments, string $partId = ''): void
    {
        $currentPartId = $partId === '' ? '1' : $partId;

        $disposition = $part['disposition'] ?? null;
        $blobId = $part['blobId'] ?? null;
        $name = $part['name'] ?? null;
        $type = $part['type'] ?? 'application/octet-stream';
        $size = JmapCasts::int($part['size'] ?? null);

        if ($disposition === 'attachment' && \is_string($blobId) && $blobId !== '') {
            $attachments[] = new EmailAttachment(
                partId: $currentPartId,
                blobId: $blobId,
                name: \is_string($name) && $name !== '' ? $name : 'attachment',
                type: \is_string($type) ? $type : 'application/octet-stream',
                size: $size,
            );
        }

        if (isset($part['subParts']) && \is_array($part['subParts'])) {
            $index = 1;

            foreach ($part['subParts'] as $subPart) {
                if (!\is_array($subPart)) {
                    $index++;

                    continue;
                }

                $subPartStructure = $this->asStructurePart($subPart);

                if ($subPartStructure === null) {
                    $index++;

                    continue;
                }

                $childPartId = $partId === '' ? (string) $index : $partId . '.' . $index;
                $this->walkBodyStructure($subPartStructure, $attachments, $childPartId);
                $index++;
            }
        }
    }

    /**
     * @return null|array<string, mixed>
     */
    private function asStructurePart(mixed $part): ?array
    {
        if (!\is_array($part)) {
            return null;
        }

        $normalized = [];

        foreach ($part as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    /**
     * @return list<array{name: ?string, email: ?string}>
     */
    private function parseAddresses(mixed $addresses): array
    {
        if (!\is_array($addresses)) {
            return [];
        }

        $parsed = [];

        foreach ($addresses as $entry) {
            if (!\is_array($entry)) {
                continue;
            }
            $parsed[] = [
                'name' => JmapCasts::nullableString($entry['name'] ?? null),
                'email' => JmapCasts::nullableString($entry['email'] ?? null),
            ];
        }

        return $parsed;
    }
}
