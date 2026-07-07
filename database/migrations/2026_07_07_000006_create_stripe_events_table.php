<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotence des webhooks Stripe : un event_id traité ne l'est jamais deux fois.
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->string('event_id', 191)->primary();
            $table->dateTime('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
    }
};