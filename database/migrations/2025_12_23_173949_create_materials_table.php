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
        Schema::create('print_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_type_id')->constrained('print_material_types')->onDelete('restrict');
            $table->string('name');
            $table->decimal('price_per_kg_dkk', 10, 2);
            $table->decimal('waste_factor_pct', 6, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['material_type_id', 'name']);
        });

        // Add CHECK constraint only for databases that support it (not SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE print_materials ADD CONSTRAINT print_materials_price_per_kg_dkk_check CHECK (price_per_kg_dkk > 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_materials');
    }
};
