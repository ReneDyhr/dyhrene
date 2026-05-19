<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Actions\CreateReceiptAction;
use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Fastmail\DTOs\EmailMessage;
use App\Services\Fastmail\FastmailEmailService;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;
use App\Services\Receipts\N8nReceiptExtractor;
use App\Services\Receipts\ReceiptAttachmentStorage;
use App\Services\Receipts\ReceiptExtractedDataMapper;
use App\Services\Receipts\ReceiptExtractionFilePreparer;
use App\Services\Receipts\ReceiptMailBodyImageRenderer;
use App\Support\ReceiptDuplicateGuard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MailReceiptImportService
{
    /** @see receipts.description VARCHAR column */
    private const DESCRIPTION_MAX_LENGTH = 255;

    public function __construct(
        private readonly FastmailEmailService $emailService,
        private readonly N8nReceiptExtractor $extractor,
        private readonly ReceiptExtractedDataMapper $mapper,
        private readonly ReceiptAttachmentStorage $attachmentStorage,
        private readonly ReceiptExtractionFilePreparer $filePreparer,
        private readonly ReceiptMailBodyImageRenderer $bodyImageRenderer,
        private readonly CreateReceiptAction $createReceiptAction,
    ) {}

    public function import(User $user, string $fastmailEmailId): Receipt
    {
        if (\function_exists('set_time_limit')) {
            @\set_time_limit(300);
        }

        $classification = MailMessageClassification::query()
            ->where('fastmail_email_id', $fastmailEmailId)
            ->first();

        if ($classification === null) {
            throw new ReceiptExtractionException('Message is not classified yet.');
        }

        if ($classification->document_type !== MailDocumentTypeEnum::Receipt) {
            throw new ReceiptExtractionException('Only messages marked as receipts can be imported.');
        }

        if ($classification->receipt_id !== null) {
            throw new ReceiptExtractionException('This message has already been imported as a receipt.');
        }

        $message = $this->emailService->getMessage($fastmailEmailId);
        $description = $this->buildMailDescription($message);

        $attachment = ClassifiableMailAttachment::firstFromMessage($message);
        $filePath = null;
        $pendingStoragePath = null;

        try {
            if ($attachment !== null) {
                $bytes = $this->emailService->downloadBlob(
                    $attachment->blobId,
                    $attachment->name,
                    $attachment->type,
                );
                $payload = $this->filePreparer->fromAttachmentBytes($attachment, $bytes);
                $extractBytes = $payload['contents'];
                $extractFilename = $payload['filename'];

                try {
                    $output = $this->extractor->extract($extractBytes, $extractFilename);
                } finally {
                    ($payload['cleanup'])();
                }

                $pendingStoragePath = $this->attachmentStorage->storePreparedImageBytes(
                    $extractBytes,
                    $extractFilename,
                );
                $filePath = $pendingStoragePath;
            } else {
                $bodyText = $this->resolveBodyText($message);

                if ($bodyText === null || $bodyText === '') {
                    throw new ReceiptExtractionException('No attachment or email body available to extract receipt data.');
                }

                $payload = $this->filePreparer->fromPlainText($bodyText);

                try {
                    $output = $this->extractor->extract($payload['contents'], $payload['filename']);
                } finally {
                    ($payload['cleanup'])();
                }

                $bodyImageBytes = $this->bodyImageRenderer->renderToJpegBytes($message);
                $pendingStoragePath = $this->attachmentStorage->storePreparedImageBytes(
                    $bodyImageBytes,
                    'email-body.jpg',
                );
                $filePath = $pendingStoragePath;
            }

            $fallbackDate = $message->summary->receivedAt?->format('Y-m-d\TH:i');
            $fallbackVendor = $this->resolveVendor($message);
            $fallbackName = $message->summary->subject !== ''
                ? $message->summary->subject
                : ($fallbackVendor ?? 'Receipt');

            $mapped = $this->mapper->map(
                $user,
                $output,
                filePath: $filePath,
                description: $description,
                fallbackName: $fallbackName,
                fallbackVendor: $fallbackVendor,
                fallbackDate: $fallbackDate,
            );

            $receiptDate = Carbon::parse($mapped->header['date']);
            $total = 0.0;

            foreach ($mapped->items as $item) {
                $total += $item['amount'] * $item['quantity'];
            }

            if (ReceiptDuplicateGuard::duplicateExists($user, $mapped->header['vendor'] ?? null, $receiptDate, $total)) {
                $this->deleteStoragePath($pendingStoragePath);

                throw new ReceiptExtractionException(
                    'This receipt has already been uploaded. A receipt with the same vendor, time, and total price already exists.',
                );
            }

            return DB::transaction(function () use ($user, $mapped, $fastmailEmailId): Receipt {
                $header = $mapped->header;
                $header['date'] = Carbon::parse($header['date'])->format('Y-m-d H:i:s');

                $receipt = $this->createReceiptAction->handle($user, $header);

                foreach ($mapped->items as $item) {
                    $receipt->items()->create($item);
                }

                MailMessageClassification::query()
                    ->where('fastmail_email_id', $fastmailEmailId)
                    ->update([
                        'receipt_id' => $receipt->id,
                        'processed_at' => \now(),
                    ]);

                return $receipt;
            });
        } catch (\Throwable $e) {
            $this->deleteStoragePath($pendingStoragePath);

            if ($e instanceof ReceiptExtractionException) {
                throw $e;
            }

            throw new ReceiptExtractionException($e->getMessage(), 0, $e);
        }
    }

    private function buildMailDescription(EmailMessage $message): string
    {
        $lines = [
            'Imported from email',
            'Subject: ' . ($message->summary->subject !== '' ? $message->summary->subject : '(no subject)'),
            'From: ' . $message->summary->fromDisplay(),
        ];

        if ($message->summary->receivedAt !== null) {
            $lines[] = 'Received: ' . $message->summary->receivedAt->format('Y-m-d H:i:s');
        }

        return Str::limit(\implode("\n", $lines), self::DESCRIPTION_MAX_LENGTH);
    }

    private function resolveBodyText(EmailMessage $message): ?string
    {
        if ($message->textBody !== null && \trim($message->textBody) !== '') {
            return \trim($message->textBody);
        }

        if ($message->htmlBody === null || \trim($message->htmlBody) === '') {
            return null;
        }

        $decoded = \html_entity_decode($message->htmlBody, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = \trim(\strip_tags($decoded));

        return $text !== '' ? $text : null;
    }

    private function resolveVendor(EmailMessage $message): ?string
    {
        $from = $message->from[0] ?? null;

        if (!\is_array($from)) {
            return null;
        }

        $name = $from['name'] ?? null;
        $email = $from['email'] ?? null;

        if (\is_string($name) && $name !== '') {
            return $name;
        }

        if (\is_string($email) && $email !== '') {
            return $email;
        }

        return null;
    }

    private function deleteStoragePath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (Storage::disk('wasabi')->exists($path)) {
            Storage::disk('wasabi')->delete($path);
        }
    }
}
