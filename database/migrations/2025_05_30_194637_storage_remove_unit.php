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
        Schema::table('storage_items', function (Blueprint $table): void {
            // Remove the 'unit' column from the storage_items table
            $table->dropColumn('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_items', function (Blueprint $table): void {
            // Re-add the 'unit' column to the storage_items table
            $table->string('unit')->nullable(); // Unit (e.g., bag, tub)
        });
    }
};
