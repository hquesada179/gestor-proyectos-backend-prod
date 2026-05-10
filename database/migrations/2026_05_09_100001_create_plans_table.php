<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 50)->unique();
            $table->decimal('monthly_price', 8, 2)->default(0);
            $table->unsignedInteger('monthly_ai_credits')->default(50);
            $table->unsignedInteger('daily_ai_limit')->default(20);
            $table->unsignedInteger('minute_ai_limit')->default(3);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
