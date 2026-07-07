<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('booking_ref', 32)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            // Copie du nom du véhicule au moment de la réservation (le tarif/nom peut changer ensuite).
            $table->string('vehicle_name', 191)->default('');
            $table->string('full_name', 191)->default('');
            $table->string('email', 191)->default('');
            $table->string('phone', 40)->default('');
            $table->text('pickup_address')->nullable();
            $table->text('dropoff_address')->nullable();
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->decimal('dropoff_lat', 10, 7)->nullable();
            $table->decimal('dropoff_lng', 10, 7)->nullable();
            $table->date('ride_date')->nullable()->index();
            $table->time('ride_time')->nullable();
            $table->decimal('distance_km', 10, 2)->default(0);
            $table->decimal('duration_min', 10, 2)->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 8)->default('EUR');
            $table->text('customer_message')->nullable();
            $table->string('payment_method', 20)->default('');
            $table->string('payment_status', 20)->default('pending')->index();
            $table->string('transaction_id', 191)->default('');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};