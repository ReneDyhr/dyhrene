<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Ebird\EbirdImportService;
use Illuminate\Console\Command;

class ImportEbirdObservationsCommand extends Command
{
    protected $signature = 'ebird:import
                            {--user=1 : The user ID to import for}
                            {--username= : eBird username (overrides config)}
                            {--password= : eBird password (overrides config)}';

    protected $description = 'Import eBird observations for a user';

    public function handle(EbirdImportService $service): int
    {
        $userId = (int) $this->option('user');

        /** @var ?User $user */
        $user = User::query()->find($userId);

        if ($user === null) {
            $this->error("User {$userId} not found.");

            return self::FAILURE;
        }

        $ebirdUser = $this->getEbirdUsername();
        $ebirdPass = $this->getEbirdPassword();

        if ($ebirdUser === '' || $ebirdPass === '') {
            $this->error('eBird username and password are required. Set EBIRD_USERNAME and EBIRD_PASSWORD in .env or pass --username and --password.');

            return self::FAILURE;
        }

        $this->info("Importing eBird observations for user {$user->name} (ID: {$userId})…");

        $stats = $service->import($user, $ebirdUser, $ebirdPass);

        $this->newLine();
        $this->info('Import complete:');
        $this->line("  Species created:    {$stats['species_created']}");
        $this->line("  Observations created: {$stats['observations_created']}");
        $this->line("  Observations enriched: {$stats['observations_enriched']}");

        if ($stats['errors'] !== []) {
            $this->newLine();
            $this->warn('Errors encountered:');

            foreach ($stats['errors'] as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getEbirdUsername(): string
    {
        $option = $this->option('username');

        if (\is_string($option) && $option !== '') {
            return $option;
        }

        $value = \config('services.ebird.username', '');

        return \is_string($value) ? $value : '';
    }

    private function getEbirdPassword(): string
    {
        $option = $this->option('password');

        if (\is_string($option) && $option !== '') {
            return $option;
        }

        $value = \config('services.ebird.password', '');

        return \is_string($value) ? $value : '';
    }
}
