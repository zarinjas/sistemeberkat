<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('postcode', 5)->nullable()->after('address');
            $table->string('city')->nullable()->after('postcode');
            $table->enum('gender', ['lelaki', 'perempuan'])->nullable()->after('city');
            $table->enum('marital_status', ['berkahwin', 'bujang', 'bercerai'])->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['postcode', 'city', 'gender', 'marital_status']);
        });
    }
};
