<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_operation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('member_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index('actor_user_id');
            $table->index('member_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_operation_audits');
    }
};
