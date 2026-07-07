<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Authentification passwordless via magic link.
 * Génération, envoi, vérification atomique et nettoyage des tokens.
 */
class MagicLink
{
    public const TTL_SECONDS = 86400; // 24 heures.

    public const TYPE_LOGIN = 'login';

    public const TYPE_EMAIL_CHANGE = 'email_change';

    /**
     * Génère un magic link et l'envoie par email.
     *
     * Ordre VOULU : envoyer d'abord, persister ensuite. Si l'envoi échoue,
     * aucun token n'est inséré et les tokens existants ne sont PAS invalidés —
     * l'utilisateur n'est jamais verrouillé hors de son compte par un simple
     * échec d'envoi (c'est l'inverse d'un flux transactionnel classique,
     * et c'est intentionnel — repris de la spec AUTH-10 du plugin).
     *
     * @return bool false si l'envoi de l'email a échoué.
     */
    public static function send(User $user, string $type = self::TYPE_LOGIN, ?string $recipientEmail = null, string $redirectUrl = ''): bool
    {
        $type = $type === self::TYPE_EMAIL_CHANGE ? self::TYPE_EMAIL_CHANGE : self::TYPE_LOGIN;
        $recipient = $recipientEmail ?: $user->email;

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $verifyUrl = route('magic.verify', [
            'token' => $rawToken,
            'redirect' => $redirectUrl ?: url('/'),
        ]);

        // Envoi AVANT toute persistance (opération la plus susceptible d'échouer).
        if (! self::deliverEmail($user, $recipient, $verifyUrl, $type)) {
            return false;
        }

        // Invalider les tokens actifs du MÊME type (soft), puis insérer le nouveau.
        DB::table('magic_link_tokens')
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        DB::table('magic_link_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'type' => $type,
            'expires_at' => now()->addSeconds(self::TTL_SECONDS),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    private static function deliverEmail(User $user, string $recipient, string $verifyUrl, string $type): bool
    {
        $siteName = config('app.name');
        $firstName = $user->first_name ?: $user->name;

        if ($type === self::TYPE_EMAIL_CHANGE) {
            $subject = __('Confirmez votre nouvelle adresse email — :site', ['site' => $siteName]);
            $message = __(
                "Bonjour :prenom,\n\n"
                ."Vous avez demandé à changer l'adresse email de votre compte.\n"
                ."Cliquez sur ce lien pour confirmer cette nouvelle adresse :\n\n"
                .":url\n\n"
                ."Ce lien est valable 24 heures et ne peut être utilisé qu'une seule fois.\n"
                ."Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : votre adresse actuelle reste inchangée.\n\n"
                ."L'équipe :site",
                ['prenom' => $firstName, 'url' => $verifyUrl, 'site' => $siteName]
            );
        } else {
            $subject = __('Votre lien de connexion — :site', ['site' => $siteName]);
            $message = __(
                "Bonjour :prenom,\n\n"
                ."Voici votre lien de connexion à :site :\n\n"
                .":url\n\n"
                ."Ce lien est valable 24 heures et ne peut être utilisé qu'une seule fois.\n\n"
                ."Si vous n'avez pas demandé ce lien, ignorez cet email.\n\n"
                ."L'équipe :site",
                ['prenom' => $firstName, 'url' => $verifyUrl, 'site' => $siteName]
            );
        }

        try {
            Mail::raw($message, fn ($m) => $m->to($recipient)->subject($subject));

            return true;
        } catch (\Throwable $e) {
            Log::error("Magic link vers {$recipient} : ".$e->getMessage());

            return false;
        }
    }

    /**
     * Consomme atomiquement un token (transaction + verrou de ligne) :
     * deux clics concurrents sur le même lien ne passent pas tous les deux.
     *
     * @param  int|null  $expectedUserId  Si fourni, refuse — sans consommer —
     *                                    un token appartenant à un autre utilisateur.
     * @return array{ok: bool, code: string, user_id: int, type: string}
     *                                                                   Codes d'échec : invalid | used | expired | wrong_user.
     */
    public static function claim(string $rawToken, ?int $expectedUserId = null): array
    {
        $fail = fn (string $code) => ['ok' => false, 'code' => $code, 'user_id' => 0, 'type' => ''];

        if ($rawToken === '') {
            return $fail('invalid');
        }

        $tokenHash = hash('sha256', $rawToken);

        return DB::transaction(function () use ($tokenHash, $expectedUserId, $fail) {
            $row = DB::table('magic_link_tokens')
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                return $fail('invalid');
            }
            if ($row->used_at !== null) {
                return $fail('used');
            }
            if (now()->greaterThanOrEqualTo($row->expires_at)) {
                return $fail('expired');
            }
            // Token d'un autre utilisateur → on NE consomme PAS.
            if ($expectedUserId !== null && (int) $row->user_id !== $expectedUserId) {
                return $fail('wrong_user');
            }

            DB::table('magic_link_tokens')->where('id', $row->id)->update(['used_at' => now()]);

            return [
                'ok' => true,
                'code' => '',
                'user_id' => (int) $row->user_id,
                'type' => $row->type ?: self::TYPE_LOGIN,
            ];
        });
    }

    /**
     * Supprime les tokens expirés (tâche planifiée).
     */
    public static function cleanup(): void
    {
        DB::table('magic_link_tokens')->where('expires_at', '<', now())->delete();
    }
}
