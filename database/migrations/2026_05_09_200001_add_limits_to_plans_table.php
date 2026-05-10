<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_users')->default(1)->after('minute_ai_limit');
            $table->unsignedInteger('max_projects')->default(2)->after('max_users');
            $table->boolean('is_custom')->default(false)->after('max_projects');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_users', 'max_projects', 'is_custom']);
        });
    }
};
