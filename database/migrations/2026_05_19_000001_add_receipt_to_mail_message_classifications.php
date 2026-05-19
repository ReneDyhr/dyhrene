<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mail_message_classifications', function (Blueprint $table): void {
            $table->foreignId('receipt_id')
                ->nullable()
                ->after('classified_at')
                ->constrained('receipts')
                ->nullOnDelete();
            $table->timestamp('processed_at')->nullable()->after('receipt_id');
        });
    }

    public function down(): void
    {
        Schema::table('mail_message_classifications', function (Blueprint $table): void {
            $table->dropForeign(['receipt_id']);
            $table->dropColumn(['receipt_id', 'processed_at']);
        });
    }
};
