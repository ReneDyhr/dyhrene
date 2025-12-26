<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_material_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('avg_kwh_per_hour', 8, 4);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE print_material_types ADD CONSTRAINT print_material_types_avg_kwh_per_hour_check CHECK (avg_kwh_per_hour > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_material_types');
    }
};
