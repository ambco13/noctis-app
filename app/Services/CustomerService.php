<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Comptes clients : inscription, historique d'emails, changement d'email,
 * réservations rattachées, export et suppression RGPD.
 */
class CustomerService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    /**
     * Crée un compte client (statut pending) et envoie un magic link.
     *
     * @return User|string User créé, ou code d'erreur : 'exists' | 'invalid_email'.
     */
    public static function register(string $email, string $firstName, string $lastName, ?string $password = null): User|string
    {
        $email = strtolower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }

        // Anti-doublon : email courant OU historique (un ancien email reste réservé).
        if (self::resolveUserByEmail($email)) {
            return 'exists';
        }

        $user = User::create([
            'name' => trim("$firstName $lastName") ?: $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password !== null ? Hash::make($password) : null,
            'account_status' => self::STATUS_PENDING,
        ]);

        self::addEmailHistory($user->id, $email, true);

        // Rattache les réservations invité passées avec cet email.
        Booking::where('email', $email)->whereNull('user_id')->update(['user_id' => $user->id]);

        MagicLink::send($user, MagicLink::TYPE_LOGIN);

        return $user;
    }

    /**
     * Garantit un compte client rattaché à une réservation payée (flux invité).
     *
     * Appelé après paiement confirmé : si l'email n'a aucun compte, en crée un
     * silencieusement (statut pending, sans mot de passe ni magic link — l'info
     * de création part dans l'e-mail de réservation, pas dans un e-mail séparé).
     * Si un compte existe déjà, rattache la réservation et ne met à jour le
     * profil (nom/téléphone) QUE si le compte n'est pas encore configuré : un
     * compte actif garde le nom saisi par son propriétaire.
     *
     * @return array{user: User, created: bool}
     */
    public static function ensureAccountForBooking(Booking $booking): array
    {
        $email = strtolower(trim((string) $booking->email));

        $user = self::resolveUserByEmail($email);

        if ($user) {
            // Compte non configuré : on tient le contact à jour. Configuré : on n'y touche pas.
            if ($user->account_status !== self::STATUS_ACTIVE) {
                $user->forceFill([
                    'name' => $booking->full_name ?: $user->name,
                    'phone' => $booking->phone ?: $user->phone,
                ])->save();
            }

            if ($booking->user_id === null) {
                $booking->forceFill(['user_id' => $user->id])->save();
            }

            return ['user' => $user, 'created' => false];
        }

        // account_status : non fillable, prend son défaut 'pending' en base.
        $user = User::create([
            'name' => $booking->full_name ?: $email,
            'first_name' => '',
            'last_name' => '',
            'phone' => (string) $booking->phone,
            'email' => $email,
            'password' => null,
        ]);

        self::addEmailHistory($user->id, $email, true);

        // Rattache cette réservation ET les éventuelles réservations invité passées.
        Booking::where('email', $email)->whereNull('user_id')->update(['user_id' => $user->id]);
        $booking->user_id = $user->id; // Reflète le rattachement sur l'instance en mémoire.

        return ['user' => $user, 'created' => true];
    }

    /**
     * Résout un compte par email courant, puis via l'historique (anciens emails).
     */
    public static function resolveUserByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        $user = User::where('email', $email)->first();
        if ($user) {
            return $user;
        }

        $userId = DB::table('email_history')->where('email', $email)->value('user_id');

        return $userId ? User::find($userId) : null;
    }

    public static function emailIsKnown(string $email): bool
    {
        return self::resolveUserByEmail($email) !== null;
    }

    public static function addEmailHistory(int $userId, string $email, bool $isCurrent): void
    {
        $exists = DB::table('email_history')
            ->where('user_id', $userId)->where('email', $email)->exists();

        if (! $exists) {
            DB::table('email_history')->insert([
                'user_id' => $userId,
                'email' => $email,
                'is_current' => $isCurrent,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ($isCurrent) {
            DB::table('email_history')
                ->where('user_id', $userId)->where('email', $email)
                ->update(['is_current' => true, 'updated_at' => now()]);
        }

        if ($isCurrent) {
            DB::table('email_history')
                ->where('user_id', $userId)->where('email', '!=', $email)
                ->update(['is_current' => false, 'updated_at' => now()]);
        }
    }

    /**
     * Première vérification d'email : active le compte.
     */
    public static function activate(User $user): void
    {
        if ($user->account_status !== self::STATUS_ACTIVE) {
            $user->forceFill([
                'account_status' => self::STATUS_ACTIVE,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }
    }

    /**
     * Demande de changement d'email : unicité vérifiée, email mémorisé en
     * attente, magic link de confirmation envoyé à la NOUVELLE adresse.
     *
     * @return true|string true, ou code : 'invalid_email' | 'same_email' | 'exists' | 'send_failed'.
     */
    public static function requestEmailChange(User $user, string $newEmail): true|string
    {
        $newEmail = strtolower(trim($newEmail));

        if (! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }
        if ($newEmail === strtolower($user->email)) {
            return 'same_email';
        }

        $owner = self::resolveUserByEmail($newEmail);
        if ($owner && $owner->id !== $user->id) {
            return 'exists';
        }

        $user->forceFill(['pending_email' => $newEmail])->save();

        if (! MagicLink::send($user, MagicLink::TYPE_EMAIL_CHANGE, $newEmail)) {
            // Envoi échoué : ne pas laisser un "pending" fantôme.
            $user->forceFill(['pending_email' => null])->save();

            return 'send_failed';
        }

        return true;
    }

    /**
     * Finalise un changement d'email confirmé par magic link.
     */
    public static function finalizeEmailChange(User $user): void
    {
        $newEmail = (string) $user->pending_email;
        if ($newEmail === '') {
            return;
        }

        $user->forceFill(['email' => $newEmail, 'pending_email' => null])->save();
        self::addEmailHistory($user->id, $newEmail, true);
    }

    /**
     * Réservations d'un utilisateur, classées :
     * upcoming (à venir), past (passées), cancelled — pending/failed exclues.
     * Récupère les rattachées (user_id) ET celles partageant l'email courant.
     *
     * @return array{upcoming: array, past: array, cancelled: array}
     */
    public static function getBookings(User $user): array
    {
        $today = now()->format('Y-m-d');

        $rows = Booking::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('email', $user->email);
        })
            ->whereNotIn('status', ['pending', 'failed'])
            ->orderByDesc('ride_date')->orderByDesc('ride_time')
            ->get();

        $out = ['upcoming' => [], 'past' => [], 'cancelled' => []];

        foreach ($rows as $b) {
            $item = [
                'booking_ref' => $b->booking_ref,
                'pickup_address' => $b->pickup_address,
                'dropoff_address' => $b->dropoff_address,
                'ride_date' => $b->ride_date?->format('Y-m-d'),
                'ride_time' => $b->ride_time ? substr($b->ride_time, 0, 5) : '',
                'vehicle_name' => $b->vehicle_name,
                'price' => Settings::formatPrice($b->price),
                'status' => $b->status,
            ];

            if ($b->status === 'cancelled') {
                $out['cancelled'][] = $item;
            } elseif (($item['ride_date'] ?? '') >= $today) {
                $out['upcoming'][] = $item;
            } else {
                $out['past'][] = $item;
            }
        }

        return $out;
    }

    /**
     * Réservations futures confirmées (garde avant suppression de compte).
     */
    public static function futureConfirmedBookings(User $user): array
    {
        return Booking::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('email', $user->email);
        })
            ->where('status', 'confirmed')
            ->whereDate('ride_date', '>=', now()->format('Y-m-d'))
            ->orderBy('ride_date')
            ->get(['id', 'booking_ref', 'ride_date', 'ride_time'])
            ->map(fn ($b) => [
                'booking_ref' => $b->booking_ref,
                'ride_date' => $b->ride_date?->format('Y-m-d'),
                'ride_time' => $b->ride_time ? substr($b->ride_time, 0, 5) : '',
            ])->all();
    }

    /**
     * Export RGPD (portabilité). Ne contient jamais de secret
     * (token_hash, stripe_customer_id, IDs internes).
     */
    public static function exportData(User $user): array
    {
        return [
            'profil' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at?->toDateTimeString(),
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            ],
            'emails' => DB::table('email_history')->where('user_id', $user->id)
                ->get(['email', 'is_current', 'created_at'])->toArray(),
            'reservations' => Booking::where('user_id', $user->id)->orWhere('email', $user->email)
                ->get(['booking_ref', 'pickup_address', 'dropoff_address', 'ride_date', 'ride_time',
                    'vehicle_name', 'price', 'currency', 'status', 'created_at'])->toArray(),
        ];
    }

    /**
     * Suppression de compte (RGPD) : refusée s'il reste des réservations
     * futures confirmées ; sinon anonymise les réservations et supprime le
     * compte (les tokens et l'historique suivent en cascade).
     *
     * @return true|array true, ou la liste des réservations futures bloquantes.
     */
    public static function deleteAccount(User $user): true|array
    {
        $future = self::futureConfirmedBookings($user);
        if ($future !== []) {
            return $future;
        }

        DB::transaction(function () use ($user) {
            // Emails connus du compte (courant + anciens) : sert à purger les fiches contact.
            $emails = DB::table('email_history')->where('user_id', $user->id)->pluck('email')->all();
            $emails[] = $user->email;

            // Anonymisation : la réservation reste (comptabilité) sans données personnelles.
            Booking::where('user_id', $user->id)->orWhere('email', $user->email)->update([
                'user_id' => null,
                'full_name' => __('Compte supprimé'),
                'email' => '',
                'phone' => '',
                'customer_message' => null,
            ]);

            // Fiche contact = PII pure, aucune valeur comptable (registre tenu par Stripe/PayPal)
            // → suppression définitive, pas d'anonymisation.
            Customer::whereIn('email', array_unique($emails))->delete();

            $user->delete(); // email_history, magic_link_tokens, name_aliases : FK cascade.
        });

        return true;
    }
}
