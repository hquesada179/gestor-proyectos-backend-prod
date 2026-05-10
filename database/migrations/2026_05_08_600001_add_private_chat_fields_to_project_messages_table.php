<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('project_messages', 'sender_id')) {
                $table->foreignId('sender_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('project_messages', 'receiver_id')) {
                $table->foreignId('receiver_id')
                    ->nullable()
                    ->after('sender_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('project_messages', 'type')) {
                $table->string('type', 20)
                    ->default('general')
                    ->after('receiver_id');
            }
        });

        DB::table('project_messages')
            ->whereNull('sender_id')
            ->update(['sender_id' => DB::raw('user_id')]);

        DB::table('project_messages')
            ->whereNull('type')
            ->update(['type' => 'general']);

        Schema::table('project_messages', function (Blueprint $table) {
            $table->index(['project_id', 'type', 'id'], 'project_messages_type_lookup_idx');
            $table->index(['project_id', 'type', 'sender_id', 'receiver_id', 'id'], 'project_messages_private_idx');
        });
    }

    public function down(): void
    {
        Schema::table('project_messages', function (Blueprint $table) {
            $table->dropIndex('project_messages_private_idx');
            $table->dropIndex('project_messages_type_lookup_idx');

            if (Schema::hasColumn('project_messages', 'receiver_id')) {
                $table->dropForeign(['receiver_id']);
                $table->dropColumn('receiver_id');
            }

            if (Schema::hasColumn('project_messages', 'sender_id')) {
                $table->dropForeign(['sender_id']);
                $table->dropColumn('sender_id');
            }

            if (Schema::hasColumn('project_messages', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
