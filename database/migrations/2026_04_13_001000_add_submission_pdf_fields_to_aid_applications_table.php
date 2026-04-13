<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aid_applications', function (Blueprint $table) {
            $table->string('submission_pdf_path')->nullable()->after('payment_receipt_path');
            $table->timestamp('submission_pdf_generated_at')->nullable()->after('submission_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('aid_applications', function (Blueprint $table) {
            $table->dropColumn(['submission_pdf_path', 'submission_pdf_generated_at']);
        });
    }
};
