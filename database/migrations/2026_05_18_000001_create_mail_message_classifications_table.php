<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mail_message_classifications', function (Blueprint $table): void {
            $table->id();
            $table->string('fastmail_email_id')->unique();
            $table->string('document_type');
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('source');
            $table->timestamp('classified_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_message_classifications');
    }
};
