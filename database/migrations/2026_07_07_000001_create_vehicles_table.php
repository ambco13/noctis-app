<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->default('');
            $table->text('description')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->decimal('price_per_km', 10, 2)->default(0);
            $table->decimal('price_per_min', 10, 2)->default(0);
            $table->decimal('min_price', 10, 2)->default(0);
            $table->unsignedTinyInteger('capacity')->default(4);
            $table->unsignedTinyInteger('luggage')->default(2);
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};