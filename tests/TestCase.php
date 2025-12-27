<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\RunsMigrations;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RunsMigrations;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations for in-memory SQLite
        if ($this->app->make('db')->connection()->getDriverName() === 'sqlite') {
            $this->runMigrations();
        }
    }
}
