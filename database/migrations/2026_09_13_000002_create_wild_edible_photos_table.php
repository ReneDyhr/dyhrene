<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wild_edible_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wild_edible_id')->constrained()->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('original_file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['wild_edible_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wild_edible_photos');
    }
};
