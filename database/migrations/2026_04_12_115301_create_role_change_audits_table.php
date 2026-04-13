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
        Schema::create('role_change_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User whose role was changed');
            $table->foreignId('changed_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Superadmin who made the change');
            $table->string('old_role')
                ->comment('Previous role (applicant, admin, superadmin)');
            $table->string('new_role')
                ->comment('New role assigned');
            $table->text('reason')
                ->nullable()
                ->comment('Reason for role change');
            $table->timestamp('changed_at')
                ->useCurrent()
                ->comment('When the change occurred');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('changed_by_user_id');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_change_audits');
    }
};
