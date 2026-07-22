<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('birdnet_detections', function (Blueprint $table): void {
            $table->id();
            $table->string('detection_uuid')->unique();
            $table->string('scientific_name');
            $table->string('common_name');
            $table->decimal('confidence', 5, 4);
            $table->float('start_time');
            $table->float('end_time');
            $table->dateTime('recorded_at');
            $table->float('latitude');
            $table->float('longitude');
            $table->string('audio_path')->nullable();
            $table->string('segment_id')->nullable();
            $table->json('raw_metadata')->nullable();
            $table->foreignId('species_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('observation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('scientific_name');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birdnet_detections');
    }
};
