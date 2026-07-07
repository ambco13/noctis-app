<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Clés API gérées depuis le back-office : chiffrées au repos (APP_KEY via
 * Crypt) dans la table settings, jamais renvoyées en clair à l'interface.
 * Priorité : valeur saisie dans l'admin, sinon .env (config/services.php).
 */
class Secrets
{
    /** Clés gérées → chemin config de secours (.env). */
    public const KEYS = [
        'google_maps_key' => 'services.google_maps.key',
        'stripe_key' => 'services.stripe.key',
        'stripe_secret' => 'services.stripe.secret',
        'stripe_webhook_secret' => 'services.stripe.webhook_secret',
        'paypal_client_id' => 'services.paypal.client_id',
        'paypal_secret' => 'services.paypal.secret',
        'twilio_sid' => 'services.twilio.sid',
        'twilio_token' => 'services.twilio.token',
        'twilio_from' => 'services.twilio.from',
    ];

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
