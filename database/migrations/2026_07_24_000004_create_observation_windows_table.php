<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('observation_windows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->dateTime('window_start');
            $table->string('source');
            $table->integer('records')->default(1);
            $table->decimal('max_confidence', 5, 4)->nullable();
            $table->timestamps();

            $table->unique(
                ['site_id', 'species_id', 'window_start', 'source'],
                'observation_windows_unique',
            );
            $table->index(['site_id', 'species_id']);
            $table->index('window_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observation_windows');
    }
};
