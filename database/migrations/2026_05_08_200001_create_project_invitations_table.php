<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('invited_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invited_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->string('email');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->string('token', 64)->nullable()->unique();
            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // A user can only have one active invitation per project
            $table->index(['proyecto_id', 'email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invitations');
    }
};
