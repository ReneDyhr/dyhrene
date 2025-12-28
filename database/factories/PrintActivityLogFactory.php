<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrintActivityLog;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PrintActivityLog>
 */
class PrintActivityLogFactory extends Factory
{
    protected $model = PrintActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'print_job_id' => PrintJob::factory(),
            'action' => \fake()->randomElement(['lock', 'unlock', 'create', 'update', 'delete']),
            'user_id' => User::factory(),
            'metadata' => \fake()->optional()->randomElement([
                null,
                ['reason' => 'Order completed'],
                ['note' => 'Customer requested changes'],
            ]),
        ];
    }
}
