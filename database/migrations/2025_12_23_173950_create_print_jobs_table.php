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
        Schema::create('print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('order_no')->unique();
            $table->date('date');
            $table->text('description');
            $table->mediumText('internal_notes')->nullable();
            $table->foreignId('customer_id')->constrained('print_customers')->onDelete('restrict');
            $table->foreignId('material_id')->constrained('print_materials')->onDelete('restrict');
            $table->unsignedInteger('pieces_per_plate');
            $table->unsignedInteger('plates');
            $table->decimal('grams_per_plate', 10, 2);
            $table->decimal('hours_per_plate', 10, 3);
            $table->decimal('labor_hours', 10, 3)->default(0);
            $table->boolean('is_first_time_order')->default(false);
            $table->decimal('avance_pct_override', 6, 2)->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('locked_at')->nullable();
            $table->json('calc_snapshot')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
