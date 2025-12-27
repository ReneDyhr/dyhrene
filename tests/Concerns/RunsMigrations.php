<?php

declare(strict_types=1);

namespace Tests\Concerns;

trait RunsMigrations
{
    /**
     * Run migrations for the test database.
     */
    protected function runMigrations(): void
    {
        $migrator = $this->app->make('migrator');
        $paths = [database_path('migrations')];
        
        if (!$migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }
        
        $migrator->run($paths);
    }
}


