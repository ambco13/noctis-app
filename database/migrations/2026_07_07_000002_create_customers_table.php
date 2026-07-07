<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contacts issus des réservations invité (sans compte utilisateur).
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 191)->default('');
            $table->string('email', 191)->default('')->index();
            $table->string('phone', 40)->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};