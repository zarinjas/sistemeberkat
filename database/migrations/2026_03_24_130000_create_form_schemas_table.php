<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('category_key', 50)->index();
            $table->string('category_name');
            $table->string('version', 20)->default('v1');
            $table->json('schema_json');
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['category_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_schemas');
    }
};
