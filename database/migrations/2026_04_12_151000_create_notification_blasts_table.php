<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_blasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type', 20);
            $table->json('target_meta')->nullable();
            $table->string('subject', 150);
            $table->text('message');
            $table->json('channels');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->json('recipient_user_ids')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_blasts');
    }
};
