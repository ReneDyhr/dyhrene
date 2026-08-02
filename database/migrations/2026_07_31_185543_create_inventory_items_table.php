<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('owner')->default('shared');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('current_value', 10, 2)->nullable();
            $table->string('acquisition_type')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->string('acquired_from')->nullable();
            $table->string('status')->default('owned');
            $table->date('status_change_date')->nullable();
            $table->string('status_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
