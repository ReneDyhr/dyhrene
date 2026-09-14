<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pickings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wild_edible_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('picked_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('comment')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'picked_at']);
            $table->index(['wild_edible_id', 'picked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickings');
    }
};
