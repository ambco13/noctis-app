<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleSeeder extends Seeder
{
    /**
     * Véhicules d'exemple (repris de l'activation du plugin d'origine).
     * N'insère rien si la table contient déjà des véhicules.
     */
    public function run(): void
    {
        if (DB::table('vehicles')->exists()) {
            return;
        }

        $now = now();

        DB::table('vehicles')->insert([
            [
                'name' => 'Berline',
                'description' => "Confort discret, idéal jusqu'à 4 passagers.",
                'base_fare' => 5.00,
                'price_per_km' => 1.80,
                'price_per_min' => 0.40,
                'min_price' => 15.00,
                'capacity' => 4,
                'luggage' => 2,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Van',
                'description' => "Espace généreux jusqu'à 7 passagers et bagages.",
                'base_fare' => 8.00,
                'price_per_km' => 2.40,
                'price_per_min' => 0.55,
                'min_price' => 25.00,
                'capacity' => 7,
                'luggage' => 6,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}