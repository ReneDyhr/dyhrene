<?php

declare(strict_types=1);

namespace App\Livewire\Mail;

use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Fastmail\DTOs\Mailbox;
use App\Services\Fastmail\EmailQuery;
use App\Services\Fastmail\Exceptions\FastmailApiException;
use App\Services\Fastmail\Exceptions\FastmailConfigurationException;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Fastmail\FastmailIdentityService;
use App\Services\Fastmail\FastmailMailboxService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Inbox extends Component
{
    public string $mailboxId = '';

    public string $from = '';

    public string $subject = '';

    public string $since = '';

    public bool $hasAttachment = false;

    public bool $showAllAccountMail = false;

    public string $searchText = '';

    public string $recipientEmail = '';

    public ?string $selectedEmailId = null;

    public string $error = '';

    public bool $loading = false;

    public bool $hasMore = false;

    public int $total = 0;

    public ?string $queryState = null;

    /**
     * @var list<array{id: string, name: string, role: ?string, unreadEmails: int}>
     */
    public array $mailboxes = [];

    /**
     * @var list<array{
     *     id: string,
     *     subject: string,
     *     fromDisplay: string,
     *     receivedAt: ?string,
     *     preview: ?string,
     *     hasAttachment: bool
     * }>
     */
    public array $emails = [];

    /**
     * @var list<string>
     */
    public array $accountAddresses = [];

    public function mount(
        FastmailMailboxService $mailboxService,
        FastmailEmailService $emailService,
        FastmailIdentityService $identityService,
    ): void {
        try {
            $this->recipientEmail = $identityService->configuredRecipient();

            try {
                $this->accountAddresses = $identityService->listIdentities()
                    ->pluck('email')
                    ->all();
            } catch (FastmailApiException) {
                $this->accountAddresses = [$this->recipientEmail];
            }

            $mailboxList = $mailboxService->listMailboxes();

            $this->mailboxes = $mailboxList
                ->map(static fn(Mailbox $mailbox): array => [
                    'id' => $mailbox->id,
                    'name' => $mailbox->name,
                    'role' => $mailbox->role,
                    'unreadEmails' => $mailbox->unreadEmails,
                ])
                ->values()
                ->all();

            $inbox = $mailboxList->first(
                static fn(Mailbox $mailbox): bool => $mailbox->role === 'inbox',
            );

            if ($inbox !== null) {
                $this->mailboxId = $inbox->id;
            } elseif ($this->mailboxes !== []) {
                $this->mailboxId = $this->mailboxes[0]['id'];
            }

            $this->fetchEmails($emailService, reset: true);
        } catch (FastmailConfigurationException $e) {
            $this->error = $e->getMessage();
        } catch (FastmailApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function applyFilters(FastmailEmailService $emailService): void
    {
        $this->selectedEmailId = null;
        $this->fetchEmails($emailService, reset: true);
    }

    public function loadMore(FastmailEmailService $emailService): void
    {
        $this->fetchEmails($emailService, reset: false);
    }

    public function selectEmail(string $id): void
    {
        $this->selectedEmailId = $id;
    }

    public function clearSelection(): void
    {
        $this->selectedEmailId = null;
    }

    public function downloadAttachment(
        FastmailEmailService $emailService,
        string $blobId,
        string $filename,
        string $mimeType,
    ): StreamedResponse {
        $bytes = $emailService->downloadBlob($blobId);
        $safeName = $filename !== '' ? $filename : 'attachment';

        return \response()->streamDownload(
            static function () use ($bytes): void {
                echo $bytes;
            },
            $safeName,
            [
                'Content-Type' => $mimeType !== '' ? $mimeType : 'application/octet-stream',
            ],
        );
    }

    public function render(FastmailEmailService $emailService): View
    {
        $selectedMessage = $this->resolveSelectedMessage($emailService);

        return \view('mail.inbox', [
            'title' => 'Mail',
            'selectedMessage' => $selectedMessage,
            'recipientEmail' => $this->recipientEmail,
            'accountAddresses' => $this->accountAddresses,
        ]);
    }

    private function resolveSelectedMessage(FastmailEmailService $emailService): ?EmailMessage
    {
        if ($this->selectedEmailId === null || $this->selectedEmailId === '') {
            return null;
        }

        try {
            return $emailService->getMessage($this->selectedEmailId);
        } catch (FastmailApiException | FastmailConfigurationException $e) {
            $this->error = $e->getMessage();

            return null;
        }
    }

    private function fetchEmails(FastmailEmailService $emailService, bool $reset): void
    {
        $this->loading = true;
        $this->error = '';

        try {
            $query = $this->buildQuery($emailService, $reset);
            $search = $emailService->search($query);
            $rows = $search['summaries']
                ->map(static fn(EmailSummary $summary): array => self::summaryToRow($summary))
                ->values()
                ->all();

            if ($reset) {
                $this->emails = $rows;
            } else {
                $this->emails = \array_merge($this->emails, $rows);
            }

            $result = $search['result'];
            $this->total = $result->total;
            $this->queryState = $result->queryState;
            $this->hasMore = \count($this->emails) < $this->total;
        } catch (FastmailApiException | FastmailConfigurationException $e) {
            $this->error = $e->getMessage();

            if ($reset) {
                $this->emails = [];
            }
        } finally {
            $this->loading = false;
        }
    }

    /**
     * @return array{
     *     id: string,
     *     subject: string,
     *     fromDisplay: string,
     *     receivedAt: ?string,
     *     preview: ?string,
     *     hasAttachment: bool
     * }
     */
    private static function summaryToRow(EmailSummary $summary): array
    {
        return [
            'id' => $summary->id,
            'subject' => $summary->subject,
            'fromDisplay' => $summary->fromDisplay(),
            'receivedAt' => $summary->receivedAt?->format('Y-m-d H:i'),
            'preview' => $summary->preview,
            'hasAttachment' => $summary->hasAttachment,
        ];
    }

    private function buildQuery(FastmailEmailService $emailService, bool $reset): EmailQuery
    {
        $query = $this->showAllAccountMail
            ? new EmailQuery()
            : $emailService->defaultQuery();

        $query = $query->limit(25);

        if ($reset) {
            $query = $query->position(0)->queryState(null);
        } else {
            $position = \count($this->emails);
            $query = $query->position($position);

            if ($this->queryState !== null && $this->queryState !== '') {
                $query = $query->queryState($this->queryState);
            }
        }

        if ($this->mailboxId !== '') {
            $query = $query->inMailbox($this->mailboxId);
        }

        if ($this->from !== '') {
            $query = $query->from($this->from);
        }

        if ($this->subject !== '') {
            $query = $query->subject($this->subject);
        }

        if ($this->since !== '') {
            $query = $query->receivedAfter(Carbon::parse($this->since)->startOfDay());
        }

        if ($this->hasAttachment) {
            $query = $query->hasAttachment(true);
        }

        if ($this->searchText !== '') {
            $query = $query->text($this->searchText);
        }

        return $query;
    }
}
