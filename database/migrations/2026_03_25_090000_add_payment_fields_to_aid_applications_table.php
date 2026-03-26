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
            $table->decimal('paid_amount', 12, 2)->nullable()->after('requested_amount');
            $table->string('transaction_ref')->nullable()->after('paid_amount');
            $table->timestamp('paid_at')->nullable()->after('decided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aid_applications', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'transaction_ref', 'paid_at']);
        });
    }
};
