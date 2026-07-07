<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historique multi-noms d'un compte (réservations invité rattachées).
        Schema::create('name_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 100)->default('');
            $table->string('last_name', 100)->default('');
            $table->string('source', 50)->default('guest_booking');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('name_aliases');
    }
};