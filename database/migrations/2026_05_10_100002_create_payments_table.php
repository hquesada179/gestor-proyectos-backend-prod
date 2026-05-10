<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('wompi');
            $table->string('provider_payment_id')->nullable();         // ID de la transacción en Wompi
            $table->string('reference')->unique();                     // referencia única GP-{user}-{plan}-{ts}
            $table->unsignedInteger('amount');                         // centavos
            $table->string('currency', 5)->default('COP');
            $table->string('status', 20)->default('pending');          // pending|approved|rejected|failed|voided
            $table->json('raw_payload')->nullable();                   // payload completo del evento
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
