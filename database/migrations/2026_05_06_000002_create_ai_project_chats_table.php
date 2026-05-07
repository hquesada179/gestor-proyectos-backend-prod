<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_project_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();

            // What kind of action this chat entry represents
            $table->string('tipo_accion', 50)->default('consulta_general');
            // crear_proyecto | editar_proyecto | generar_requerimientos | generar_tareas
            // generar_sprints | generar_insumos | consulta_general

            // The raw prompt the user typed
            $table->text('prompt_usuario');

            // The improved/final prompt sent to Ollama (optional)
            $table->text('prompt_mejorado')->nullable();

            // The raw text response from Ollama
            $table->longText('respuesta_ia')->nullable();

            // Structured data extracted from the AI response (JSON)
            $table->json('datos_detectados')->nullable();

            // Lifecycle state of this chat entry
            $table->string('estado', 20)->default('borrador');
            // borrador | aplicado | descartado | error

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_project_chats');
    }
};
