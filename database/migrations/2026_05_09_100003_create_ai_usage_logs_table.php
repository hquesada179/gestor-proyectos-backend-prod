<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->string('action_type', 50);
            $table->unsignedInteger('credits_charged')->default(0);
            $table->unsignedInteger('estimated_input_tokens')->nullable();
            $table->unsignedInteger('estimated_output_tokens')->nullable();
            $table->unsignedInteger('actual_input_tokens')->nullable();
            $table->unsignedInteger('actual_output_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 10, 6)->nullable();
            $table->decimal('actual_cost_usd', 10, 6)->nullable();
            $table->string('status', 20)->default('pending'); // pending | success | failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
