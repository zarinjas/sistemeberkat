<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aid_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_no')->nullable()->unique();
            $table->string('status')->default('draft')->index();
            $table->json('triage_answers')->nullable();
            $table->json('dynamic_payload')->nullable();
            $table->json('category_tags')->nullable();
            $table->decimal('requested_amount', 12, 2)->nullable();
            $table->unsignedInteger('priority_score')->default(0)->index();
            $table->string('priority_label')->nullable()->index();
            $table->string('priority_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aid_applications');
    }
};
