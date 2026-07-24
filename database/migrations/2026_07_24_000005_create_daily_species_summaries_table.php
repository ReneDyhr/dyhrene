<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_species_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->integer('windows_present')->default(0);
            $table->integer('records')->default(0);
            $table->json('sources')->nullable();
            $table->dateTime('first_seen_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['site_id', 'date', 'species_id'],
                'daily_species_summaries_unique',
            );
            $table->index(['site_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_species_summaries');
    }
};
