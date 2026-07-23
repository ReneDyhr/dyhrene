<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('birdnet_detections', function (Blueprint $table): void {
            // Composite index for deduplication queries: segment_id + time window + user
            // Null segment_ids are excluded by the index (MySQL skips NULLs in composites)
            $table->index(['segment_id', 'start_time', 'end_time', 'user_id'], 'birdnet_detections_segment_dedup_index');
        });
    }

    public function down(): void
    {
        Schema::table('birdnet_detections', function (Blueprint $table): void {
            $table->dropIndex('birdnet_detections_segment_dedup_index');
        });
    }
};
