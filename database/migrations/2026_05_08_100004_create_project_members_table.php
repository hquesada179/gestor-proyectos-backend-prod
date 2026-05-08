<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->string('name');
            $table->string('email');
            $table->string('position')->nullable();
            $table->enum('status', ['activo', 'inactivo', 'invitado', 'suspendido'])->default('activo');
            $table->enum('work_mode', ['presencial', 'remoto', 'hibrido'])->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
