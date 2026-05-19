<?php

declare(strict_types=1);

namespace App\Livewire\Mail;

use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Models\User;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\DTOs\EmailSummary;
use App\Services\Fastmail\EmailQuery;
use App\Services\Fastmail\Exceptions\FastmailApiException;
use App\Services\Fastmail\Exceptions\FastmailConfigurationException;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Fastmail\FastmailIdentityService;
use App\Services\Fastmail\FastmailMailboxService;
use App\Services\Mail\MailDocumentClassificationService;
use App\Services\Mail\MailReceiptImportService;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Inbox extends Component
{
    private const PAGE_SIZE = 25;

    public string $archiveMailboxId = '';

    public string $recipientEmail = '';

    public ?string $selectedEmailId = null;

    public string $error = '';

    public bool $loading = false;

    public bool $hasMore = false;

    public int $total = 0;

    public ?string $queryState = null;

    public bool $processingReceipt = false;

    /**
     * @var list<array{
     *     id: string,
     *     subject: string,
     *     fromDisplay: string,
     *     receivedAt: ?string,
     *     preview: ?string,
     *     hasAttachment: bool,
     *     documentType: ?string,
     *     documentTypeLabel: ?string,
     *     receiptId: ?int,
     *     receiptImportLabel: ?string,
     *     receiptImportStatus: ?string
     * }>
     */
    public array $emails = [];

    public function mount(
        FastmailMailboxService $mailboxService,
        FastmailEmailService $emailService,
        FastmailIdentityService $identityService,
        MailDocumentClassificationService $classificationService,
    ): void {
        try {
            $this->recipientEmail = $identityService->configuredRecipient();
            $archive = $mailboxService->findDefaultMailbox();

            if ($archive === null) {
                $this->error = 'Archive mailbox not found.';

                return;
            }

            $this->archiveMailboxId = $archive->id;
            $this->fetchEmails($emailService, $classificationService, reset: true);
        } catch (FastmailConfigurationException $e) {
            $this->error = $e->getMessage();
        } catch (FastmailApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function loadMore(
        FastmailEmailService $emailService,
        MailDocumentClassificationService $classificationService,
    ): void {
        $this->fetchEmails($emailService, $classificationService, reset: false);
    }

    public function selectEmail(string $id): void
    {
        $this->selectedEmailId = $id;
    }

    public function clearSelection(): void
    {
        $this->selectedEmailId = null;
    }

    public function processReceipt(
        MailReceiptImportService $importService,
        string $emailId,
    ): void {
        $user = \Auth::user();

        if (!$user instanceof User) {
            Session::flash('error', 'Unauthorized.');

            return;
        }

        $this->processingReceipt = true;

        try {
            $receipt = $importService->import($user, $emailId);
            $this->refreshRowClassification(
                $emailId,
                \app(MailDocumentClassificationService::class),
            );
            Session::flash(
                'success',
                'Receipt created. <a href="' . \route('receipts.show', ['receipt' => $receipt->id]) . '">View receipt</a>',
            );
        } catch (ReceiptExtractionException $e) {
            Log::warning('Mail receipt import failed', [
                'email_id' => $emailId,
                'message' => $e->getMessage(),
            ]);
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Mail receipt import error', [
                'email_id' => $emailId,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            Session::flash('error', 'Failed to create receipt: ' . $e->getMessage());
        } finally {
            $this->processingReceipt = false;
        }
    }

    public function setDocumentType(
        MailDocumentClassificationService $classificationService,
        string $emailId,
        string $type,
    ): void {
        $documentType = MailDocumentTypeEnum::tryFrom($type);

        if ($documentType === null) {
            return;
        }

        $classificationService->applyManualType($emailId, $documentType);
        $this->refreshRowClassification($emailId, $classificationService);
    }

    public function downloadAttachment(
        FastmailEmailService $emailService,
        string $blobId,
        string $filename,
        string $mimeType,
    ): StreamedResponse {
        $bytes = $emailService->downloadBlob($blobId, $filename, $mimeType);
        $safeName = $filename !== '' ? $filename : 'attachment';

        return \response()->streamDownload(
            static function () use ($bytes): void {
                $stream = \fopen('php://output', 'wb');

                if ($stream === false) {
                    return;
                }

                \fwrite($stream, $bytes);
                \fclose($stream);
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
        $selectedClassification = null;

        if ($this->selectedEmailId !== null && $this->selectedEmailId !== '') {
            $selectedClassification = MailMessageClassification::query()
                ->where('fastmail_email_id', $this->selectedEmailId)
                ->first();
        }

        return \view('mail.inbox', [
            'title' => 'Mail',
            'selectedMessage' => $selectedMessage,
            'selectedClassification' => $selectedClassification,
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

    private function fetchEmails(
        FastmailEmailService $emailService,
        MailDocumentClassificationService $classificationService,
        bool $reset,
    ): void {
        if ($this->archiveMailboxId === '') {
            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $search = $emailService->search($this->buildQuery($emailService, $reset));
            $rows = $search['summaries']
                ->map(static fn(EmailSummary $summary): array => self::summaryToRow($summary))
                ->values()
                ->all();

            if ($reset) {
                $this->emails = \array_values($rows);
            } else {
                $this->emails = \array_merge($this->emails, $rows);
            }

            $this->enrichWithClassifications($classificationService);

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

    private function enrichWithClassifications(MailDocumentClassificationService $classificationService): void
    {
        $ids = \array_map(
            static fn(array $row): string => $row['id'],
            $this->emails,
        );

        if ($ids === []) {
            return;
        }

        $classificationService->classifyMissing($ids);
        $classifications = $classificationService->getForEmailIds($ids);

        foreach ($this->emails as $index => $row) {
            $classification = $classifications->get($row['id']);
            $this->emails[$index] = self::applyClassificationToRow($row, $classification);
        }
    }

    private function refreshRowClassification(
        string $emailId,
        MailDocumentClassificationService $classificationService,
    ): void {
        $classification = $classificationService->getForEmailIds([$emailId])->get($emailId);

        foreach ($this->emails as $index => $row) {
            if ($row['id'] !== $emailId) {
                continue;
            }

            $this->emails[$index] = self::applyClassificationToRow($row, $classification);

            break;
        }
    }

    /**
     * @param array{
     *     id: string,
     *     subject: string,
     *     fromDisplay: string,
     *     receivedAt: ?string,
     *     preview: ?string,
     *     hasAttachment: bool,
     *     documentType: ?string,
     *     documentTypeLabel: ?string,
     *     receiptId: ?int,
     *     receiptImportLabel: ?string,
     *     receiptImportStatus: ?string
     * } $row
     *
     * @return array{
     *     id: string,
     *     subject: string,
     *     fromDisplay: string,
     *     receivedAt: ?string,
     *     preview: ?string,
     *     hasAttachment: bool,
     *     documentType: ?string,
     *     documentTypeLabel: ?string,
     *     receiptId: ?int,
     *     receiptImportLabel: ?string,
     *     receiptImportStatus: ?string
     * }
     */
    private static function applyClassificationToRow(array $row, ?MailMessageClassification $classification): array
    {
        if ($classification === null) {
            $row['documentType'] = null;
            $row['documentTypeLabel'] = null;
            $row['receiptId'] = null;
            $row['receiptImportLabel'] = null;
            $row['receiptImportStatus'] = null;

            return $row;
        }

        $row['documentType'] = $classification->document_type->value;
        $row['documentTypeLabel'] = $classification->document_type->label();
        $row['receiptId'] = $classification->receipt_id;
        [$row['receiptImportLabel'], $row['receiptImportStatus']] = self::receiptImportDisplay(
            $classification->document_type,
            $classification->receipt_id,
        );

        return $row;
    }

    /**
     * @return array{0: ?string, 1: ?string} label and status key (created|pending|null)
     */
    private static function receiptImportDisplay(MailDocumentTypeEnum $type, ?int $receiptId): array
    {
        if ($type !== MailDocumentTypeEnum::Receipt) {
            return [null, null];
        }

        if ($receiptId !== null) {
            return ['Created', 'created'];
        }

        return ['Not created', 'pending'];
    }

    /**
     * @return array{
     *     id: string,
     *     subject: string,
     *     fromDisplay: string,
     *     receivedAt: ?string,
     *     preview: ?string,
     *     hasAttachment: bool,
     *     documentType: ?string,
     *     documentTypeLabel: ?string,
     *     receiptId: ?int,
     *     receiptImportLabel: ?string,
     *     receiptImportStatus: ?string
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
            'documentType' => null,
            'documentTypeLabel' => null,
            'receiptId' => null,
            'receiptImportLabel' => null,
            'receiptImportStatus' => null,
        ];
    }

    private function buildQuery(FastmailEmailService $emailService, bool $reset): EmailQuery
    {
        $query = $emailService->defaultQuery()
            ->inMailbox($this->archiveMailboxId)
            ->limit(self::PAGE_SIZE);

        if ($reset) {
            return $query->position(0)->queryState(null);
        }

        $query = $query->position(\count($this->emails));

        if ($this->queryState !== null && $this->queryState !== '') {
            $query = $query->queryState($this->queryState);
        }

        return $query;
    }
}
