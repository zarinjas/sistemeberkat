<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guideline_pages', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_published');
        });

        $pages = DB::table('guideline_pages')->orderBy('created_at')->get(['id']);

        foreach ($pages as $index => $page) {
            DB::table('guideline_pages')
                ->where('id', $page->id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('guideline_pages', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
