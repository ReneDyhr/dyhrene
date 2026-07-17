<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table): void {
            $table->id();
            $table->string('common_name');
            $table->string('scientific_name');
            $table->string('ebird_code', 10)->nullable()->unique();
            $table->integer('taxonomic_order')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('common_name');
            $table->unique(['common_name', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};
