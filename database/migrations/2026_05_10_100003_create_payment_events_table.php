<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->default('wompi');
            $table->string('event_type', 60)->nullable();              // transaction.updated, etc.
            $table->string('provider_event_id')->nullable()->unique(); // para idempotencia
            $table->json('payload');                                   // cuerpo completo del webhook
            $table->timestamp('processed_at')->nullable();             // null = sin procesar
            $table->timestamps();

            $table->index(['provider', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
