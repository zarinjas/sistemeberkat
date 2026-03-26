<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('form_schemas', function (Blueprint $table) {
            $table->string('lifecycle_status', 20)->default('published')->after('is_active')->index();
        });

        DB::table('form_schemas')
            ->where('is_active', true)
            ->update(['lifecycle_status' => 'published']);

        DB::table('form_schemas')
            ->where('is_active', false)
            ->update(['lifecycle_status' => 'archived']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_schemas', function (Blueprint $table) {
            $table->dropColumn('lifecycle_status');
        });
    }
};
