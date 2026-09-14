<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wild_edibles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location_name')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedTinyInteger('season_start_month')->nullable();
            $table->unsignedTinyInteger('season_end_month')->nullable();
            $table->boolean('season_all_year')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'season_all_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wild_edibles');
    }
};
