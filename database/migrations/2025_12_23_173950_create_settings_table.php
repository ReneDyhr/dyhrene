<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('electricity_rate_dkk_per_kwh', 10, 4)->nullable();
            $table->decimal('wage_rate_dkk_per_hour', 10, 2)->nullable();
            $table->decimal('default_avance_pct', 6, 2)->nullable();
            $table->decimal('first_time_fee_dkk', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_settings');
    }
};
