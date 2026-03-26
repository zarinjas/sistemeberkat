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
        Schema::table('aid_applications', function (Blueprint $table) {
            $table->foreignId('payment_prepared_by_user_id')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_prepared_at')->nullable()->after('payment_prepared_by_user_id');
            $table->foreignId('payment_approved_by_user_id')->nullable()->after('payment_prepared_at')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_approved_at')->nullable()->after('payment_approved_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aid_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_prepared_by_user_id');
            $table->dropColumn('payment_prepared_at');
            $table->dropConstrainedForeignId('payment_approved_by_user_id');
            $table->dropColumn('payment_approved_at');
        });
    }
};
