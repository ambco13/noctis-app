<?php

namespace App\Services;

use App\Events\BookingConfirmed;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Support\Settings;
use Illuminate\Support\Str;

/**
 * Service métier de réservation : crée la réservation (statut "en attente"),
 * la confirme après paiement vérifié côté serveur, déclenche les notifications.
 */
class BookingService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    /** Clé de session mémorisant la réservation en attente (anti-doublon). */
    private const SESSION_PENDING = 'pending_booking_id';

    /**
     * Vide toute la session du tunnel (trajet étape 1 + réservation en attente),
     * appelé après une confirmation de paiement réussie.
     */
    public static function clearFlowSession(): void
    {
        session()->forget([self::SESSION_PENDING, 'booking_step1']);
    }

    public static function generateRef(): string
    {
        do {
            $ref = 'NTB-'.strtoupper(Str::random(8));
        } while (Booking::where('booking_ref', $ref)->exists());

        return $ref;
    }

    /**
     * Crée une réservation à l'état "en attente" avant paiement.
     *
     * @param  array  $input  Données client + course (déjà validées par le contrôleur).
     *
     * @throws BookingException
     */
    public static function createPending(array $input): Booking
    {
        // Supprimer l'ancienne réservation en attente de cette session (anti-doublon).
        $oldId = session(self::SESSION_PENDING);
        if ($oldId) {
            Booking::where('id', $oldId)->where('status', self::STATUS_PENDING)->delete();
            session()->forget(self::SESSION_PENDING);
        }

        $vehicle = Vehicle::find($input['vehicle_id']);
        if (! $vehicle) {
            throw new BookingException(__('Véhicule introuvable.'));
        }

        // Recalcul du trajet et du prix CÔTÉ SERVEUR (ne jamais faire confiance au prix client).
        $route = GoogleMaps::computeRoute(
            $input['pickup_address'],
            $input['dropoff_address'],
            $input['pickup_place_id'] ?? '',
            $input['dropoff_place_id'] ?? ''
        );

        $context = [
            'ride_date' => $input['ride_date'],
            'ride_time' => $input['ride_time'] ?? '',
        ];
        $price = Pricing::calculate($vehicle, $route['distance_km'], $route['duration_min'], $context);

        $customer = Customer::updateOrCreate(
            ['email' => $input['email']],
            ['full_name' => $input['full_name'], 'phone' => $input['phone']]
        );

        $booking = Booking::create([
            'booking_ref' => self::generateRef(),
            'user_id' => auth()->id(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => $vehicle->name,
            'full_name' => $input['full_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'pickup_address' => $route['origin'],
            'dropoff_address' => $route['destination'],
            'ride_date' => $input['ride_date'],
            'ride_time' => ! empty($input['ride_time']) ? $input['ride_time'].':00' : null,
            'distance_km' => $route['distance_km'],
            'duration_min' => $route['duration_min'],
            'price' => $price,
            'currency' => Settings::currency(),
            'customer_message' => $input['customer_message'] ?? '',
            'payment_method' => $input['payment_method'] ?? '',
            'payment_status' => 'pending',
            'status' => self::STATUS_PENDING,
        ]);

        // Mémoriser en session pour pouvoir nettoyer en cas de nouvelle tentative.
        session([self::SESSION_PENDING => $booking->id]);

        return $booking;
    }

    /**
     * Confirme une réservation après vérification serveur du paiement.
     * Idempotent : ne renvoie pas les notifications si déjà confirmée.
     *
     * @throws BookingException
     */
    public static function confirmPaid(int $bookingId, string $transactionId): Booking
    {
        $booking = Booking::find($bookingId);
        if (! $booking) {
            throw new BookingException(__('Réservation introuvable.'));
        }

        // Déjà confirmée : on ne refait rien (anti double-notification webhook + retour navigateur).
        if ($booking->status === self::STATUS_CONFIRMED && $booking->payment_status === 'paid') {
            return $booking;
        }

        $booking->update([
            'payment_status' => 'paid',
            'status' => self::STATUS_CONFIRMED,
            'transaction_id' => $transactionId,
        ]);

        session()->forget(self::SESSION_PENDING);

        BookingConfirmed::dispatch($booking);

        return $booking;
    }

    /**
     * Données publiques sûres d'une réservation (écran de confirmation).
     */
    public static function publicSummary(Booking $booking): array
    {
        $dateFmt = (string) Settings::get('date_format', 'd/m/Y');
        $timeFmt = (string) Settings::get('time_format', 'H:i');

        return [
            'booking_ref' => $booking->booking_ref,
            'full_name' => $booking->full_name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'pickup' => $booking->pickup_address,
            'dropoff' => $booking->dropoff_address,
            'date' => $booking->ride_date ? $booking->ride_date->format($dateFmt) : '',
            'time' => $booking->ride_time ? date($timeFmt, strtotime($booking->ride_time)) : '',
            'vehicle' => $booking->vehicle_name,
            'distance_km' => $booking->distance_km,
            'duration_min' => $booking->duration_min,
            'price' => Settings::formatPrice($booking->price),
            'status' => $booking->status,
        ];
    }
}
