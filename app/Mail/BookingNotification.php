<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail HTML de réservation (branding NOCTIS) : corps texte issu des
 * templates éditables + tableau récapitulatif de la réservation.
 */
class BookingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $body,
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking',
            with: [
                'paragraphs' => preg_split('/\n{2,}/', trim($this->body)) ?: [],
                'booking' => $this->booking,
            ],
        );
    }
}
