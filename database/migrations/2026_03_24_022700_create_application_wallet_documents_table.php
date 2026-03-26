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
        Schema::create('application_wallet_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aid_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_document_id')->constrained()->cascadeOnDelete();
            $table->string('relation_type')->default('supporting');
            $table->timestamps();

            $table->unique(['aid_application_id', 'wallet_document_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_wallet_documents');
    }
};
