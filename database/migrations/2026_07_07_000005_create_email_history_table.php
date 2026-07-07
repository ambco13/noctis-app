<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historique des emails d'un compte : retrouver un compte via un ancien
        // email (ghost booking) et bloquer la réutilisation d'un email connu.
        Schema::create('email_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email', 191)->default('')->index();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_history');
    }
};