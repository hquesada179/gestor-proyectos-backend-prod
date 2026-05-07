<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_project_chats', function (Blueprint $table) {
            // Self-referential FK: points to the "parent" chat that was reused.
            // nullOnDelete: if parent is deleted, child keeps its data but loses the link.
            $table->foreignId('parent_chat_id')
                ->nullable()
                ->after('proyecto_id')
                ->constrained('ai_project_chats')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_project_chats', function (Blueprint $table) {
            $table->dropForeign(['parent_chat_id']);
            $table->dropColumn('parent_chat_id');
        });
    }
};
