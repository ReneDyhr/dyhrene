<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table): void {
            $table->foreignId('site_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('location_raw')->nullable()->after('location');
            $table->date('local_date')->nullable()->after('location_raw');
            $table->time('local_time')->nullable()->after('local_date');
            $table->integer('minutes_from_sunrise')->nullable()->after('local_time');
            $table->integer('minutes_from_sunset')->nullable()->after('minutes_from_sunrise');
            $table->smallInteger('day_of_year')->nullable()->after('minutes_from_sunset');

            $table->index(['site_id', 'local_date']);
            $table->index(['site_id', 'species_id', 'local_date']);
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table): void {
            $table->dropIndex(['site_id', 'species_id', 'local_date']);
            $table->dropIndex(['site_id', 'local_date']);
            $table->dropForeign(['site_id']);
            $table->dropColumn([
                'site_id',
                'location_raw',
                'local_date',
                'local_time',
                'minutes_from_sunrise',
                'minutes_from_sunset',
                'day_of_year',
            ]);
        });
    }
};
