<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Services\Notifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Envoie les notifications hors du cycle de la requête HTTP (file d'attente),
 * pour ne pas bloquer la réponse en cas de timeout SMTP ou Twilio —
 * équivalent du wp_schedule_single_event du plugin d'origine.
 */
class SendBookingNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BookingConfirmed $event): void
    {
        Notifications::sendForBooking($event->booking);
    }
}
