<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cache des métadonnées de cartes — la source de vérité reste Stripe.
        // Feature "cartes enregistrées" désactivée côté UI pour l'instant.
        Schema::create('customer_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_customer_id', 64)->default('')->index();
            $table->string('payment_method_id', 64)->unique();
            $table->string('brand', 20)->default('');
            $table->string('last4', 4)->default('');
            $table->unsignedTinyInteger('exp_month')->default(0);
            $table->unsignedSmallInteger('exp_year')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_cards');
    }
};