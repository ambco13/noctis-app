<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\Stripe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Confirme un paiement Stripe au retour navigateur — revérifié côté serveur,
     * on ne se fie jamais au seul retour du front.
     */
    public function confirmStripe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'integer'],
            'payment_intent_id' => ['required', 'string', 'max:255'],
        ]);

        $booking = Booking::find($validated['booking_id']);
        if (! $booking) {
            return $this->error(__('Réservation introuvable.'));
        }

        // Liaison réservation <-> PaymentIntent : le PaymentIntent reçu doit être
        // celui créé pour cette réservation, empêche la confirmation d'une
        // réservation tierce avec un intent valide appartenant à un autre client.
        if (empty($booking->transaction_id) || $booking->transaction_id !== $validated['payment_intent_id']) {
            return $this->error(__('Paiement invalide.'));
        }

        if (! Stripe::isPaid($validated['payment_intent_id'])) {
            return $this->error(__('Paiement non confirmé.'));
        }

        $booking = BookingService::confirmPaid($booking->id, $validated['payment_intent_id']);
        BookingService::clearFlowSession();

        return response()->json([
            'success' => true,
            'summary' => BookingService::publicSummary($booking),
        ]);
    }

    /**
     * Webhook Stripe : source de vérité de la confirmation de paiement.
     * Signature vérifiée puis idempotence (at-most-once) via la table stripe_events.
     */
    public function webhookStripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (! Stripe::verifyWebhook($payload, (string) $request->header('stripe-signature'))) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true) ?: [];
        $eventId = (string) ($event['id'] ?? '');
        $type = (string) ($event['type'] ?? '');
        $object = (array) ($event['data']['object'] ?? []);

        // Idempotence — déjà traité ?
        if ($eventId !== '' && DB::table('stripe_events')->where('event_id', $eventId)->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        // Marquer AVANT le traitement (at-most-once intentionnel) : une erreur
        // partielle se résout par réconciliation, jamais par un retry Stripe —
        // rejouer un payment_intent.succeeded doublerait la confirmation.
        if ($eventId !== '') {
            DB::table('stripe_events')->insert(['event_id' => $eventId, 'processed_at' => now()]);
        }

        try {
            $this->dispatchStripeEvent($type, $object);
        } catch (\Throwable $e) {
            Log::error("Webhook Stripe ($type) : ".$e->getMessage());

            // 200 pour empêcher Stripe de rejouer l'événement (réconciliation manuelle).
            return response()->json(['status' => 'error_logged']);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Dispatch d'un événement Stripe déjà vérifié et marqué.
     */
    private function dispatchStripeEvent(string $type, array $obj): void
    {
        $ref = (string) ($obj['metadata']['booking_ref'] ?? '');
        $txn = (string) ($obj['id'] ?? '');

        switch ($type) {
            case 'payment_intent.succeeded':
                $booking = $ref !== '' ? Booking::where('booking_ref', $ref)->first() : null;
                if ($booking) {
                    BookingService::confirmPaid($booking->id, $txn);
                }
                break;

            case 'payment_intent.payment_failed':
                $booking = $ref !== '' ? Booking::where('booking_ref', $ref)->first() : null;
                if ($booking && $booking->status === BookingService::STATUS_PENDING) {
                    $booking->update([
                        'payment_status' => 'failed',
                        'status' => BookingService::STATUS_FAILED,
                    ]);
                }
                break;

                // Les événements de cartes enregistrées (setup_intent.succeeded,
                // payment_method.attached/detached) seront réintégrés avec la feature.
        }
    }

    private function error(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }
}
