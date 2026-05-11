<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Prevents duplicate conversations between the same two users in a project
            $table->unique(['project_id', 'user_one_id', 'user_two_id'], 'pc_project_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_conversations');
    }
};
