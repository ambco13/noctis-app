<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Réglages éditables depuis le back-office (devise, templates, notifications…).
        // Les secrets (clés API Stripe/PayPal/Twilio/Google) restent dans .env/config.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};