<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('observed_at');
            $table->time('observed_time')->nullable();
            $table->string('count')->nullable();
            $table->string('location')->nullable();
            $table->string('state_province', 10)->nullable();
            $table->string('ebird_submission_id', 20)->nullable()->unique();
            $table->string('observation_type')->nullable();
            $table->integer('duration_min')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('area_ha', 8, 2)->nullable();
            $table->integer('observer_count')->default(1);
            $table->boolean('complete_checklist')->default(false);
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->index('observed_at');
            $table->index('ebird_submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
