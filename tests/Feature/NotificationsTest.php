<?php

namespace Tests\Feature;

use App\Events\BookingConfirmed;
use App\Listeners\SendBookingNotifications;
use App\Mail\BookingNotification;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\Notifications;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Véhicules + réglages par défaut (templates, toggles).
        Settings::flush();
    }

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    private function booking(): Booking
    {
        return Booking::create([
            'booking_ref' => 'NTB-NOTIF001',
            'vehicle_id' => 1,
            'vehicle_name' => 'Berline',
            'full_name' => 'Amir Cohen',
            'email' => 'client@example.com',
            'phone' => '+33612345678',
            'pickup_address' => 'Paris',
            'dropoff_address' => 'Versailles',
            'ride_date' => '2026-08-15',
            'ride_time' => '14:30:00',
            'distance_km' => 10,
            'price' => 35,
            'currency' => 'EUR',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
    }

    public function test_les_tags_sont_substitues(): void
    {
        $tags = Notifications::buildTags($this->booking());

        $this->assertSame('NTB-NOTIF001', $tags['{numero_reservation}']);
        $this->assertSame('Paris → Versailles', $tags['{trajet}']);
        $this->assertSame('15/08/2026', $tags['{date}']);
        $this->assertSame('14:30', $tags['{heure}']);
        $this->assertSame('35,00 €', $tags['{prix}']);

        $this->assertSame(
            'Votre réservation NTB-NOTIF001 est confirmée',
            Notifications::render('Votre réservation {numero_reservation} est confirmée', $tags)
        );
    }

    public function test_email_client_et_admin_envoyes_selon_les_reglages(): void
    {
        Mail::fake();
        Settings::set('admin_notify_email', 'admin@example.com');
        Settings::flush();

        Notifications::sendForBooking($this->booking());

        Mail::assertSent(BookingNotification::class, fn ($m) => $m->hasTo('client@example.com'));
        Mail::assertSent(BookingNotification::class, fn ($m) => $m->hasTo('admin@example.com'));
        Mail::assertSentCount(2);
    }

    public function test_email_client_desactive_nest_pas_envoye(): void
    {
        Mail::fake();
        Settings::set('notif_email_customer', '0');
        Settings::set('admin_notify_email', 'admin@example.com');
        Settings::flush();

        Notifications::sendForBooking($this->booking());

        Mail::assertSent(BookingNotification::class, 1);
        Mail::assertSent(BookingNotification::class, fn ($m) => $m->hasTo('admin@example.com'));
    }

    public function test_le_rendu_html_contient_le_recapitulatif(): void
    {
        $mail = new BookingNotification('Sujet', "Bonjour Amir,\n\nMerci.", $this->booking());
        $html = $mail->render();

        $this->assertStringContainsString('NTB-NOTIF001', $html);
        $this->assertStringContainsString('NOCTIS', $html);
        $this->assertStringContainsString('Berline', $html);
        $this->assertStringContainsString('35,00', $html);
    }

    public function test_sms_client_envoye_via_twilio_quand_active(): void
    {
        Mail::fake();
        config(['services.twilio.sid' => 'AC123', 'services.twilio.token' => 'tok', 'services.twilio.from' => '+1000']);
        Settings::set('notif_sms_customer', '1');
        Settings::flush();

        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        Notifications::sendForBooking($this->booking());

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.twilio.com')
                && $request['To'] === '+33612345678'
                && str_contains($request['Body'], 'NTB-NOTIF001');
        });
    }

    public function test_confirmation_declenche_le_listener_en_file(): void
    {
        Event::fake([BookingConfirmed::class]);

        $booking = $this->booking();
        $booking->update(['status' => 'pending', 'payment_status' => 'pending']);

        BookingService::confirmPaid($booking->id, 'pi_test');

        Event::assertDispatched(BookingConfirmed::class);
        Event::assertListening(BookingConfirmed::class, SendBookingNotifications::class);
    }
}
