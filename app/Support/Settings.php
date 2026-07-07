<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Réglages applicatifs stockés dans la table `settings`.
 * Cache en mémoire par requête. Les secrets (clés API) ne passent pas ici :
 * ils vivent dans config/services.php.
 */
class Settings
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    private static bool $faked = false;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::load();

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]
        );

        self::$cache[$key] = (string) $value;
    }

    /**
     * Remplace tous les réglages par un jeu en mémoire (tests unitaires, sans DB).
     */
    public static function fake(array $values = []): void
    {
        self::$cache = $values;
        self::$faked = true;
    }

    public static function flush(): void
    {
        self::$cache = null;
        self::$faked = false;
    }

    public static function currency(): string
    {
        return (string) self::get('currency', 'EUR');
    }

    public static function currencySymbol(): string
    {
        return (string) self::get('currency_symbol', '€');
    }

    /**
     * Format français : 12,50 €.
     */
    public static function formatPrice(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' '.self::currencySymbol();
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = DB::table('settings')->pluck('value', 'key')->all();
    }
}
