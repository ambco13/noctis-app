<?php

namespace App\Services;

use App\Mail\BookingNotification;
use App\Models\Booking;
use App\Support\Secrets;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifications de réservation : e-mails HTML et SMS Twilio.
 * Chaque canal est activable/désactivable indépendamment (table settings).
 * Templates éditables avec variables {nom}, {trajet}, {date}, {heure},
 * {vehicule}, {prix}, {numero_reservation}, {email}, {telephone}, {message}.
 */
class Notifications
{
    /**
     * Envoie toutes les notifications activées pour une réservation confirmée.
     */
    public static function sendForBooking(Booking $booking): void
    {
        $tags = self::buildTags($booking);

        if (Settings::get('notif_email_customer') && $booking->email) {
            self::sendEmail(
                $booking->email,
                self::render((string) Settings::get('tpl_email_customer_subject'), $tags),
                self::render((string) Settings::get('tpl_email_customer_body'), $tags),
                $booking
            );
        }

        if (Settings::get('notif_email_admin')) {
            $adminEmail = (string) Settings::get('admin_notify_email', '');
            if ($adminEmail !== '') {
                self::sendEmail(
                    $adminEmail,
                    self::render((string) Settings::get('tpl_email_admin_subject'), $tags),
                    self::render((string) Settings::get('tpl_email_admin_body'), $tags),
                    $booking
                );
            }
        }

        if (Settings::get('notif_sms_customer') && $booking->phone) {
            self::sendSms($booking->phone, self::render((string) Settings::get('tpl_sms_customer'), $tags));
        }

        if (Settings::get('notif_sms_admin')) {
            $adminPhone = (string) Settings::get('admin_notify_phone', '');
            if ($adminPhone !== '') {
                self::sendSms($adminPhone, self::render((string) Settings::get('tpl_sms_admin'), $tags));
            }
        }
    }

    /**
     * Variables de substitution d'une réservation.
     *
     * @return array<string, string>
     */
    public static function buildTags(Booking $booking): array
    {
        $dateFmt = (string) Settings::get('date_format', 'd/m/Y');
        $timeFmt = (string) Settings::get('time_format', 'H:i');

        return [
            '{nom}' => $booking->full_name,
            '{trajet}' => $booking->pickup_address.' → '.$booking->dropoff_address,
            '{date}' => $booking->ride_date ? $booking->ride_date->format($dateFmt) : '',
            '{heure}' => $booking->ride_time ? date($timeFmt, strtotime($booking->ride_time)) : '',
            '{vehicule}' => $booking->vehicle_name,
            '{prix}' => Settings::formatPrice($booking->price),
            '{numero_reservation}' => $booking->booking_ref,
            '{email}' => $booking->email,
            '{telephone}' => $booking->phone,
            '{message}' => (string) $booking->customer_message,
        ];
    }

    /**
     * Remplace les variables dans un template.
     */
    public static function render(string $template, array $tags): string
    {
        return strtr($template, $tags);
    }

    private static function sendEmail(string $to, string $subject, string $body, Booking $booking): void
    {
        try {
            Mail::to($to)->send(new BookingNotification($subject, $body, $booking));
        } catch (\Throwable $e) {
            // Une notification qui échoue ne doit jamais casser le flux de réservation.
            Log::error("Email réservation {$booking->booking_ref} vers {$to} : ".$e->getMessage());
        }
    }

    /**
     * Envoie un SMS via l'API Twilio.
     */
    public static function sendSms(string $to, string $body): bool
    {
        $sid = Secrets::get('twilio_sid');
        $token = Secrets::get('twilio_token');
        $from = Secrets::get('twilio_from');

        if ($sid === '' || $token === '' || $from === '') {
            Log::warning('SMS non envoyé : identifiants Twilio incomplets.');

            return false;
        }

        try {
            $response = Http::timeout(20)
                ->withBasicAuth($sid, $token)
                ->asForm()
                ->post('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($sid).'/Messages.json', [
                    'To' => $to,
                    'From' => $from,
                    'Body' => $body,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("SMS vers {$to} : ".$e->getMessage());

            return false;
        }
    }
}
