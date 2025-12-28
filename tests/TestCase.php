<?php

declare(strict_types=1);

namespace Tests;

use App\Models\PrintCustomer;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialType;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\RunsMigrations;

/**
 * @property User $user
 * @property PrintCustomer $customer
 * @property PrintMaterialType $materialType
 * @property PrintMaterial $material
 * @property PrintJob $printJob
 * @phpstan-property User $user
 * @phpstan-property PrintCustomer $customer
 * @phpstan-property PrintMaterialType $materialType
 * @phpstan-property PrintMaterial $material
 * @phpstan-property PrintJob $printJob
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RunsMigrations;

    protected User $user;

    protected PrintCustomer $customer;

    protected PrintMaterialType $materialType;

    protected PrintMaterial $material;

    protected PrintJob $printJob;

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
