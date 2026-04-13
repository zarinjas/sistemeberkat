<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_schemas', function (Blueprint $table) {
            $table->string('card_color', 20)->nullable()->after('category_name');
        });
    }

    public function down(): void
    {
        Schema::table('form_schemas', function (Blueprint $table) {
            $table->dropColumn('card_color');
        });
    }
};
