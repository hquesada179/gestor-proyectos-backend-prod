<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_project_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Workflow state
            $table->string('status', 50)->default('pending');
            // pending | interpreted | mode_selected | project_ready | requirements_ready
            // tasks_ready | sprints_ready | supplies_ready | completed | cancelled

            $table->string('selected_mode', 20)->nullable();
            // guided | automatic | base_only

            // Prompts
            $table->text('original_prompt');
            $table->text('improved_prompt')->nullable();

            // Generated data (stored as JSON for each phase)
            $table->json('interpretation_data')->nullable();
            $table->json('project_data')->nullable();
            $table->json('requirements_data')->nullable();
            $table->json('tasks_data')->nullable();
            $table->json('sprints_data')->nullable();
            $table->json('supplies_data')->nullable();

            // Raw AI responses for debugging/recovery
            $table->json('raw_responses')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_project_drafts');
    }
};
