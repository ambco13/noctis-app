<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Clés API gérées depuis le back-office : chiffrées au repos (APP_KEY via
 * Crypt) dans la table settings, jamais renvoyées en clair à l'interface.
 * Priorité : valeur saisie dans l'admin, sinon .env (config/services.php).
 *
 * Stripe et PayPal ont des paires test/live ; le réglage `test_mode`
 * détermine la paire effective (comme le plugin d'origine).
 */
class Secrets
{
    /** Clés gérées → chemin config de secours (.env). */
    public const KEYS = [
        'google_maps_key' => 'services.google_maps.key',

        'stripe_key' => 'services.stripe.key',
        'stripe_secret' => 'services.stripe.secret',
        'stripe_key_live' => 'services.stripe.key_live',
        'stripe_secret_live' => 'services.stripe.secret_live',
        'stripe_webhook_secret' => 'services.stripe.webhook_secret',

        'paypal_client_id' => 'services.paypal.client_id',
        'paypal_secret' => 'services.paypal.secret',
        'paypal_client_id_live' => 'services.paypal.client_id_live',
        'paypal_secret_live' => 'services.paypal.secret_live',

        'twilio_sid' => 'services.twilio.sid',
        'twilio_token' => 'services.twilio.token',
        'twilio_from' => 'services.twilio.from',
    ];

    /**
     * Mode test (sandbox) : Stripe et PayPal utilisent les clés de test.
     */
    public static function isTestMode(): bool
    {
        return (string) Settings::get('test_mode', '1') === '1';
    }

    /* ── Clés effectives selon le mode ───────────────────────── */

    public static function stripeKey(): string
    {
        return self::get(self::isTestMode() ? 'stripe_key' : 'stripe_key_live');
    }

    public static function stripeSecret(): string
    {
        return self::get(self::isTestMode() ? 'stripe_secret' : 'stripe_secret_live');
    }

    public static function paypalClientId(): string
    {
        return self::get(self::isTestMode() ? 'paypal_client_id' : 'paypal_client_id_live');
    }

    public static function paypalSecret(): string
    {
        return self::get(self::isTestMode() ? 'paypal_secret' : 'paypal_secret_live');
    }

    /**
     * Valeur effective : admin (déchiffrée) en priorité, sinon .env.
     */
    public static function get(string $key): string
    {
        $stored = (string) Settings::get('secret_'.$key, '');

        if ($stored !== '') {
            try {
                return Crypt::decryptString($stored);
            } catch (DecryptException) {
                // APP_KEY changée ou valeur corrompue : on retombe sur le .env.
            }
        }

        return trim((string) config(self::KEYS[$key] ?? ''));
    }

    /**
     * Enregistre (chiffré) ; chaîne vide = suppression de l'override admin.
     */
    public static function set(string $key, string $value): void
    {
        if (! array_key_exists($key, self::KEYS)) {
            return;
        }

        Settings::set('secret_'.$key, $value === '' ? '' : Crypt::encryptString($value));
    }

    /**
     * Affichage masqué pour l'admin : source + 4 derniers caractères.
     */
    public static function masked(string $key): string
    {
        $stored = (string) Settings::get('secret_'.$key, '');
        $value = self::get($key);

        if ($value === '') {
            return '';
        }

        $suffix = strlen($value) > 4 ? substr($value, -4) : '';

        return ($stored !== '' ? __('définie (admin)') : __('définie (.env)')).' ····'.$suffix;
    }
}
