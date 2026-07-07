<?php

namespace Tests\Unit;

use App\Services\Pricing;
use App\Support\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Tests du moteur de prix (portés du plugin d'origine).
 *
 * Formule : prix = base + (km × distance) + (min × durée), planché au
 * prix minimum, puis majorations (nuit / week-end / férié, cumulatives).
 *
 * Valeurs attendues vérifiées par calcul indépendant (Pâques 2026 = 05/04,
 * 11/07/2026 = samedi, 14/07/2026 = mardi férié, 15/08/2026 = samedi férié).
 */
final class PricingTest extends TestCase
{
    /**
     * Véhicule de référence : base 5 €, 2 €/km, 0,50 €/min, minimum 10 €.
     */
    private function vehicle(array $overrides = []): object
    {
        return (object) array_merge([
            'base_fare' => 5.0,
            'price_per_km' => 2.0,
            'price_per_min' => 0.5,
            'min_price' => 10.0,
        ], $overrides);
    }

    protected function setUp(): void
    {
        // Aucune majoration active par défaut ; chaque test active ce dont il a besoin.
        Settings::fake();
    }

    protected function tearDown(): void
    {
        Settings::flush();
    }

    /* ---------------------------------------------------------------------
     * Formule de base et plancher
     * ------------------------------------------------------------------ */

    public function test_formule_de_base(): void
    {
        // 5 + 2×10 + 0,5×20 = 35.
        $this->assertSame(35.0, Pricing::calculate($this->vehicle(), 10, 20));
    }

    public function test_plancher_prix_minimum(): void
    {
        // 5 + 2×1 + 0,5×2 = 8 < min 10 → 10.
        $this->assertSame(10.0, Pricing::calculate($this->vehicle(), 1, 2));
    }

    public function test_arrondi_deux_decimales(): void
    {
        $v = $this->vehicle(['min_price' => 0.0]);
        $this->assertSame(7.67, Pricing::calculate($v, 1.333, 0));
    }

    public function test_distance_et_duree_nulles_donnent_le_minimum(): void
    {
        $this->assertSame(10.0, Pricing::calculate($this->vehicle(), 0, 0));
    }

    /* ---------------------------------------------------------------------
     * Majorations
     * ------------------------------------------------------------------ */

    public function test_sans_date_aucune_majoration(): void
    {
        Settings::fake(['surcharge_weekend_enabled' => 1, 'surcharge_weekend_pct' => 20]);
        $this->assertSame(35.0, Pricing::calculate($this->vehicle(), 10, 20, []));
    }

    public function test_majoration_weekend(): void
    {
        Settings::fake(['surcharge_weekend_enabled' => 1, 'surcharge_weekend_pct' => 20]);
        $ctx = ['ride_date' => '2026-07-11']; // Samedi.
        $this->assertSame(42.0, Pricing::calculate($this->vehicle(), 10, 20, $ctx));
    }

    public function test_pas_de_majoration_weekend_en_semaine(): void
    {
        Settings::fake(['surcharge_weekend_enabled' => 1, 'surcharge_weekend_pct' => 20]);
        $ctx = ['ride_date' => '2026-07-06']; // Lundi.
        $this->assertSame(35.0, Pricing::calculate($this->vehicle(), 10, 20, $ctx));
    }

    public function test_majoration_nuit(): void
    {
        Settings::fake([
            'surcharge_night_enabled' => 1,
            'surcharge_night_start' => '22:00',
            'surcharge_night_end' => '06:00',
            'surcharge_night_pct' => 25,
        ]);
        $ctx = ['ride_date' => '2026-07-06', 'ride_time' => '23:00'];
        $this->assertSame(43.75, Pricing::calculate($this->vehicle(), 10, 20, $ctx));
    }

    public function test_bornes_plage_nocturne(): void
    {
        Settings::fake([
            'surcharge_night_enabled' => 1,
            'surcharge_night_start' => '22:00',
            'surcharge_night_end' => '06:00',
            'surcharge_night_pct' => 25,
        ]);
        $base = ['ride_date' => '2026-07-06'];

        // 22:00 inclus (début), 05:59 inclus, 06:00 exclu (fin), 21:59 exclu.
        $this->assertSame(43.75, Pricing::calculate($this->vehicle(), 10, 20, $base + ['ride_time' => '22:00']));
        $this->assertSame(43.75, Pricing::calculate($this->vehicle(), 10, 20, $base + ['ride_time' => '05:59']));
        $this->assertSame(35.0, Pricing::calculate($this->vehicle(), 10, 20, $base + ['ride_time' => '06:00']));
        $this->assertSame(35.0, Pricing::calculate($this->vehicle(), 10, 20, $base + ['ride_time' => '21:59']));
    }

    public function test_majoration_jour_ferie(): void
    {
        Settings::fake(['surcharge_holiday_enabled' => 1, 'surcharge_holiday_pct' => 30]);
        $ctx = ['ride_date' => '2026-07-14']; // Mardi férié.
        $this->assertSame(45.5, Pricing::calculate($this->vehicle(), 10, 20, $ctx));
    }

    public function test_cumul_nuit_et_weekend(): void
    {
        Settings::fake([
            'surcharge_night_enabled' => 1,
            'surcharge_night_start' => '22:00',
            'surcharge_night_end' => '06:00',
            'surcharge_night_pct' => 25,
            'surcharge_weekend_enabled' => 1,
            'surcharge_weekend_pct' => 20,
        ]);
        // Samedi 23:00 → +45 % : 35 × 1,45 = 50,75.
        $ctx = ['ride_date' => '2026-07-11', 'ride_time' => '23:00'];
        $this->assertSame(50.75, Pricing::calculate($this->vehicle(), 10, 20, $ctx));
    }

    public function test_cumul_ferie_et_weekend(): void
    {
        Settings::fake([
            'surcharge_holiday_enabled' => 1,
            'surcharge_holiday_pct' => 30,
            'surcharge_weekend_enabled' => 1,
            'surcharge_weekend_pct' => 20,
        ]);
        // 15/08/2026 = samedi ET férié → +50 % : 35 × 1,5 = 52,50.
        $ctx = ['ride_date' => '2026-08-15'];
        $this->assertSame(52.5, Pricing::calculate($this->vehicle(), 10, 20, $ctx));
    }

    public function test_majoration_appliquee_apres_le_plancher(): void
    {
        Settings::fake(['surcharge_weekend_enabled' => 1, 'surcharge_weekend_pct' => 20]);
        // Brut 8 → planché 10 → +20 % = 12 (et non 8 × 1,2 = 9,6).
        $ctx = ['ride_date' => '2026-07-11'];
        $this->assertSame(12.0, Pricing::calculate($this->vehicle(), 1, 2, $ctx));
    }

    /* ---------------------------------------------------------------------
     * Jours fériés français
     * ------------------------------------------------------------------ */

    public function test_jours_feries_2026(): void
    {
        $holidays = Pricing::frenchHolidays(2026);

        $this->assertCount(11, $holidays);
        // Fixes.
        foreach (['2026-01-01', '2026-05-01', '2026-05-08', '2026-07-14', '2026-08-15', '2026-11-01', '2026-11-11', '2026-12-25'] as $d) {
            $this->assertContains($d, $holidays);
        }
        // Mobiles (Pâques 2026 = 5 avril).
        $this->assertContains('2026-04-06', $holidays); // Lundi de Pâques.
        $this->assertContains('2026-05-14', $holidays); // Ascension.
        $this->assertContains('2026-05-25', $holidays); // Lundi de Pentecôte.
    }

    public function test_jour_normal_absent_des_feries(): void
    {
        $this->assertNotContains('2026-07-06', Pricing::frenchHolidays(2026));
    }
}
