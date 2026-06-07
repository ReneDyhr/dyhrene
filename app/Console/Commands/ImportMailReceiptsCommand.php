<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MailDocumentTypeEnum;
use App\Models\MailMessageClassification;
use App\Models\User;
use App\Services\Mail\MailReceiptImportService;
use App\Services\Receipts\Exceptions\ReceiptExtractionException;
use Illuminate\Console\Command;

class ImportMailReceiptsCommand extends Command
{
    protected $signature = 'mail:import-receipts
                            {--id= : Import a single Fastmail email by ID}
                            {--limit=50 : Max unprocessed receipt emails to process}';

    protected $description = 'Create receipts from Fastmail messages classified as receipts';

    public function handle(MailReceiptImportService $importService): int
    {
        $user = User::query()->find(1);

        if (!$user instanceof User) {
            $this->error('User with id 1 not found.');

            return self::FAILURE;
        }

        $singleId = $this->option('id');

        if (\is_string($singleId) && $singleId !== '') {
            return $this->importSingle($importService, $user, $singleId);
        }

        return $this->importBatch($importService, $user);
    }

    private function importSingle(MailReceiptImportService $importService, User $user, string $emailId): int
    {
        $this->info('Importing email ' . $emailId . '…');

        try {
            $receipt = $importService->import($user, $emailId);
            $this->info('Receipt created: #' . $receipt->id . ' — ' . $receipt->name);
        } catch (ReceiptExtractionException $e) {
            $this->warn('Skipped: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function importBatch(MailReceiptImportService $importService, User $user): int
    {
        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $this->error('Limit must be at least 1.');

            return self::FAILURE;
        }

        $classifications = MailMessageClassification::query()
            ->where('document_type', MailDocumentTypeEnum::Receipt)
            ->whereNull('receipt_id')
            ->whereNull('processed_at')
            ->orderBy('classified_at')
            ->limit($limit)
            ->get();

        if ($classifications->isEmpty()) {
            $this->info('No unprocessed receipt emails found.');

            return self::SUCCESS;
        }

        $this->info('Processing ' . $classifications->count() . ' receipt email(s)…');

        $bar = $this->output->createProgressBar($classifications->count());
        $bar->start();

        $created = 0;
        $failed = 0;

        foreach ($classifications as $classification) {
            try {
                $importService->import($user, $classification->fastmail_email_id);
                $created++;
            } catch (ReceiptExtractionException $e) {
                $this->newLine();
                $this->warn('Skipped ' . $classification->fastmail_email_id . ': ' . $e->getMessage());
                $failed++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error('Error on ' . $classification->fastmail_email_id . ': ' . $e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done. Created: ' . $created . ', failed: ' . $failed . '.');

        return self::SUCCESS;
    }
}
