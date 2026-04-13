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
        Schema::table('dashboard_posters', function (Blueprint $table) {
            $table->string('aspect_ratio', 10)->default('1:1')->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard_posters', function (Blueprint $table) {
            $table->dropColumn('aspect_ratio');
        });
    }
};
