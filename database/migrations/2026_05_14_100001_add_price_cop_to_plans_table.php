<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Fixed COP price for Wompi (Colombia). Stored as full COP, not cents.
            // Example: 39900 = $39.900 COP. Wompi receives price_cop * 100 as amount_in_cents.
            $table->unsignedInteger('price_cop')->default(0)->after('monthly_price');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('price_cop');
        });
    }
};
