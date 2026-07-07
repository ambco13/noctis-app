<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Comptes clients : magic link d'abord, mot de passe optionnel.
            $table->string('password')->nullable()->change();
            $table->string('first_name', 100)->default('')->after('name');
            $table->string('last_name', 100)->default('')->after('first_name');
            $table->string('phone', 40)->default('')->after('last_name');
            $table->string('account_status', 20)->default('pending')->after('phone');
            $table->string('pending_email', 191)->nullable()->after('account_status');
            $table->string('stripe_customer_id', 64)->nullable()->after('pending_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'account_status', 'pending_email', 'stripe_customer_id']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
