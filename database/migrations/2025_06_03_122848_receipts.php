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
        Schema::create('receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('vendor')->nullable();
            $table->string('description')->nullable();
            $table->string('currency', 3)->default('DKK');
            $table->decimal('total', 10, 2);
            $table->dateTime('date');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('receipt_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->onDelete('cascade');
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 10, 2);
            $table->foreignId('category_id')->constrained('receipt_categories');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_items');
        Schema::dropIfExists('receipt_categories');
        Schema::dropIfExists('receipts');
    }
};
