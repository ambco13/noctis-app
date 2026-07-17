<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Émis quand une réservation passe à "confirmée" après paiement vérifié.
 * Les notifications (email/SMS) s'y abonneront en file d'attente pour ne pas
 * bloquer la réponse HTTP (équivalent du cron différé du plugin d'origine).
 */
class BookingConfirmed
{
    use Dispatchable;

    public function __construct(
        public Booking $booking,
        public bool $accountCreated = false,
    ) {}
}
