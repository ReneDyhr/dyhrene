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
        Schema::create('storage_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('storage_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Item name (e.g., fries, ice cream)
            $table->integer('quantity'); // Quantity of the item
            $table->string('unit')->nullable(); // Unit (e.g., bag, tub)
            $table->integer('sort_order')->default(0); // For ordering
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_items');
    }
};
