<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Fastmail\FastmailEmailService;
use App\Services\Fastmail\FastmailMailboxService;
use App\Services\Mail\MailDocumentClassificationService;
use Illuminate\Console\Command;

class ClassifyMailCommand extends Command
{
    protected $signature = 'mail:classify
                            {--id= : Classify a single Fastmail email id}
                            {--force : Re-classify even when cached}
                            {--limit=100 : Max messages to process when scanning the mailbox}';

    protected $description = 'Classify Fastmail messages as receipt, payslip, or unknown';

    public function handle(
        FastmailEmailService $emailService,
        FastmailMailboxService $mailboxService,
        MailDocumentClassificationService $classificationService,
    ): int {
        $singleId = $this->option('id');
        $force = \filter_var($this->option('force'), \FILTER_VALIDATE_BOOLEAN);

        if (\is_string($singleId) && $singleId !== '') {
            $this->info('Classifying email ' . $singleId . '…');
            $classificationService->classifyAndPersist($singleId, $force);
            $this->info('Done.');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $this->error('Limit must be at least 1.');

            return self::FAILURE;
        }

        $mailbox = $mailboxService->findDefaultMailbox();

        if ($mailbox === null) {
            $this->error('Archive mailbox not found.');

            return self::FAILURE;
        }

        $query = $emailService->defaultQuery()
            ->inMailbox($mailbox->id)
            ->limit($limit)
            ->position(0);

        $search = $emailService->search($query);

        /** @var list<string> $ids */
        $ids = $search['summaries']
            ->map(static fn(\App\Services\Fastmail\DTOs\EmailSummary $summary): string => $summary->id)
            ->values()
            ->all();

        if ($ids === []) {
            $this->info('No messages to classify.');

            return self::SUCCESS;
        }

        $this->info('Classifying ' . \count($ids) . ' message(s)…');

        $bar = $this->output->createProgressBar(\count($ids));
        $bar->start();

        foreach ($ids as $id) {
            $classificationService->classifyAndPersist($id, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
