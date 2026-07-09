<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Réglages par défaut (repris du plugin d'origine).
     * Les clés API (Stripe, PayPal, Twilio, Google) ne sont pas ici : elles
     * vivent dans .env / config/services.php.
     * Idempotent : ne réinsère pas une clé existante.
     */
    public function run(): void
    {
        $defaults = [
            // Général.
            'test_mode' => '1',
            // '0' : le mode "ancré au body" était un contournement pour la
            // compatibilité de themes WordPress specifiques -- inutile ici
            // (app standalone, sticky CSS classique suffit et fonctionne).
            'aside_detach_body' => '0',
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'default_country_code' => '+33',

            // Notifications ON/OFF.
            'notif_email_customer' => '1',
            'notif_email_admin' => '1',
            'notif_sms_customer' => '0',
            'notif_sms_admin' => '0',

            'admin_notify_email' => '',
            'admin_notify_phone' => '',

            // Templates email.
            'tpl_email_customer_subject' => 'Votre réservation {numero_reservation} est confirmée',
            'tpl_email_customer_body' => "Bonjour {nom},\n\nVotre réservation {numero_reservation} est confirmée.\n\nTrajet : {trajet}\nDate : {date} à {heure}\nVéhicule : {vehicule}\nMontant payé : {prix}\n\nMerci de votre confiance.",
            'tpl_email_admin_subject' => 'Nouvelle réservation {numero_reservation}',
            'tpl_email_admin_body' => "Nouvelle réservation reçue : {numero_reservation}\n\nClient : {nom}\nTrajet : {trajet}\nDate : {date} à {heure}\nVéhicule : {vehicule}\nMontant : {prix}",

            // Templates SMS.
            'tpl_sms_customer' => 'Reservation {numero_reservation} confirmee. {trajet} le {date} a {heure}. Vehicule: {vehicule}. Total: {prix}.',
            'tpl_sms_admin' => 'Nouvelle reservation {numero_reservation}: {nom}, {trajet}, {date} {heure}, {vehicule}, {prix}.',
        ];

        $now = now();

        foreach ($defaults as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
