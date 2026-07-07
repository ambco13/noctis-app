<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Support\Settings;

/**
 * Moteur de calcul de prix.
 *
 * Formule de base :
 *   Prix = prise_en_charge + (prix_km × distance) + (prix_min × durée)
 *   puis plancher au prix minimum du véhicule.
 *
 * Majorations configurables :
 *   Nuit (plage horaire), week-end (sam/dim), jours fériés français.
 *   Les pourcentages s'additionnent si plusieurs conditions se cumulent.
 */
class Pricing
{
    /**
     * Calcule le prix d'une course pour un véhicule donné.
     *
     * @param  object  $vehicle  Véhicule (base_fare, price_per_km, price_per_min, min_price).
     * @param  array  $context  { ride_date: 'Y-m-d', ride_time: 'H:i' } pour les majorations.
     */
    public static function calculate(object $vehicle, float $distanceKm, float $durationMin, array $context = []): float
    {
        $raw = (float) $vehicle->base_fare
            + ((float) $vehicle->price_per_km * $distanceKm)
            + ((float) $vehicle->price_per_min * $durationMin);

        // Plancher = prix minimum.
        $price = max($raw, (float) $vehicle->min_price);

        // Majorations configurables (nuit, week-end, jours fériés).
        $price = self::applySurcharges($price, $context);

        return round($price, 2);
    }

    /**
     * Calcule le prix de tous les véhicules actifs pour une course.
     *
     * @return array<int, array<string, mixed>> Liste enrichie pour le front.
     */
    public static function quotesForAllVehicles(float $distanceKm, float $durationMin, array $context = []): array
    {
        return Vehicle::active()->get()->map(function (Vehicle $v) use ($distanceKm, $durationMin, $context) {
            $price = self::calculate($v, $distanceKm, $durationMin, $context);

            return [
                'id' => $v->id,
                'name' => $v->name,
                'description' => $v->description,
                'image_url' => $v->image_path ? asset($v->image_path) : '',
                'capacity' => $v->capacity,
                'luggage' => $v->luggage,
                'price' => $price,
                'price_formatted' => Settings::formatPrice($price),
            ];
        })->all();
    }

    /* =========================================================================
     * MAJORATIONS
     * ====================================================================== */

    /**
     * Applique les majorations configurées. Les pourcentages s'additionnent
     * si plusieurs conditions se cumulent.
     */
    private static function applySurcharges(float $price, array $context): float
    {
        $rideDate = trim((string) ($context['ride_date'] ?? ''));
        $rideTime = trim((string) ($context['ride_time'] ?? ''));

        if ($rideDate === '') {
            return $price;
        }

        $ts = strtotime($rideDate.($rideTime !== '' ? ' '.$rideTime : ''));
        $totalPct = 0.0;

        // --- Tarif nuit ---
        if (Settings::get('surcharge_night_enabled') && $rideTime !== '') {
            $nightStart = (string) Settings::get('surcharge_night_start', '22:00');
            $nightEnd = (string) Settings::get('surcharge_night_end', '06:00');
            $nightPct = (float) Settings::get('surcharge_night_pct', 25);
            if ($nightPct > 0 && self::isNightTime($rideTime, $nightStart, $nightEnd)) {
                $totalPct += $nightPct;
            }
        }

        // --- Tarif week-end ---
        if (Settings::get('surcharge_weekend_enabled')) {
            $weekendPct = (float) Settings::get('surcharge_weekend_pct', 20);
            if ($weekendPct > 0 && (int) date('N', $ts) >= 6) { // 6=sam, 7=dim.
                $totalPct += $weekendPct;
            }
        }

        // --- Tarif jours fériés ---
        if (Settings::get('surcharge_holiday_enabled')) {
            $holidayPct = (float) Settings::get('surcharge_holiday_pct', 30);
            $year = (int) date('Y', $ts);
            if ($holidayPct > 0 && in_array(date('Y-m-d', $ts), self::frenchHolidays($year), true)) {
                $totalPct += $holidayPct;
            }
        }

        if ($totalPct > 0) {
            $price *= 1 + $totalPct / 100;
        }

        return $price;
    }

    /**
     * Vérifie si une heure tombe dans une plage nocturne (peut chevaucher minuit).
     */
    private static function isNightTime(string $time, string $nightStart, string $nightEnd): bool
    {
        $toMin = function (string $hhmm): int {
            $parts = explode(':', $hhmm);

            return (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
        };

        $t = $toMin($time);
        $s = $toMin($nightStart);
        $e = $toMin($nightEnd);

        if ($s > $e) {
            // Plage chevauche minuit : ex. 22:00 → 06:00.
            return $t >= $s || $t < $e;
        }

        // Plage dans la même journée (cas inhabituel).
        return $t >= $s && $t < $e;
    }

    /**
     * Jours fériés français d'une année donnée.
     *
     * @return string[] Dates 'Y-m-d'.
     */
    public static function frenchHolidays(int $year): array
    {
        // Calcul de Pâques (algorithme de Meeus/Jones/Butcher).
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        $easter = mktime(0, 0, 0, $month, $day, $year);

        $y = (string) $year;

        return [
            $y.'-01-01', // Jour de l'An
            date('Y-m-d', strtotime('+1 day', $easter)),   // Lundi de Pâques
            $y.'-05-01', // Fête du Travail
            $y.'-05-08', // Victoire 1945
            date('Y-m-d', strtotime('+39 days', $easter)), // Ascension
            date('Y-m-d', strtotime('+50 days', $easter)), // Lundi de Pentecôte
            $y.'-07-14', // Fête Nationale
            $y.'-08-15', // Assomption
            $y.'-11-01', // Toussaint
            $y.'-11-11', // Armistice
            $y.'-12-25', // Noël
        ];
    }
}
