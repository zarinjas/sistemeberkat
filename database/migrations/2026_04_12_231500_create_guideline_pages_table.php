<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guideline_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('draft_html')->nullable();
            $table->longText('published_html')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('guideline_pages')->insert([
            [
                'title' => 'Panduan Permohonan Bantuan',
                'slug' => 'panduan-permohonan-bantuan',
                'draft_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'published_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Prosedur Semakan Dokumen',
                'slug' => 'prosedur-semakan-dokumen',
                'draft_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'published_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Tatacara Kelulusan Dan Bayaran',
                'slug' => 'tatacara-kelulusan-dan-bayaran',
                'draft_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'published_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Kod Etika Ahli Berkat',
                'slug' => 'kod-etika-ahli-berkat',
                'draft_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'published_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Soalan Lazim Garis Panduan',
                'slug' => 'soalan-lazim-garis-panduan',
                'draft_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'published_html' => '<p>Kemaskini kandungan garis panduan di sini.</p>',
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('guideline_pages');
    }
};
