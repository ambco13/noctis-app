<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerService;
use App\Services\MagicLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Authentification des clients : inscription, magic link, mot de passe.
 * Réponses anti-énumération identiques qu'un compte existe ou non ;
 * rate limiting par email ET par IP sur toutes les routes publiques.
 */
class CustomerAuthController extends Controller
{
    /**
     * POST /auth/register (alias /auth/signup).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'max:191'],
        ]);

        if (! $this->allow('register:'.$request->ip(), 5)) {
            return $this->rateLimited();
        }

        $result = CustomerService::register(
            $validated['email'],
            $validated['first_name'] ?? '',
            $validated['last_name'] ?? '',
            $validated['password'] ?? null
        );

        // Anti-énumération : message identique compte créé ou déjà existant.
        $generic = __('Un lien de connexion vous a été envoyé par email.');

        if ($result === 'exists') {
            return response()->json(['message' => $generic]);
        }
        if ($result === 'invalid_email') {
            return response()->json(['message' => __('Adresse email invalide.')], 400);
        }

        return response()->json(['message' => $generic], 201);
    }

    /**
     * POST /auth/check — l'email correspond-il à un compte ? (rate-limité IP).
     */
    public function check(Request $request): JsonResponse
    {
        if (! $this->allow('check:'.$request->ip(), 30)) {
            return $this->rateLimited();
        }

        $validated = $request->validate(['email' => ['required', 'email', 'max:191']]);

        return response()->json(['exists' => CustomerService::emailIsKnown($validated['email'])]);
    }

    /**
     * POST /auth/magic-link/request.
     */
    public function magicLinkRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'redirect_to' => ['nullable', 'string', 'max:500'],
        ]);

        // Limite par email (contournable en variant les adresses) + par IP.
        if (! $this->allow('magic:'.strtolower($validated['email']), 5)
            || ! $this->allow('magic-ip:'.$request->ip(), 15)) {
            return $this->rateLimited();
        }

        $user = CustomerService::resolveUserByEmail($validated['email']);
        if ($user) {
            MagicLink::send($user, MagicLink::TYPE_LOGIN, null, $this->safeRedirect($validated['redirect_to'] ?? ''));
        }

        // Réponse identique qu'un compte existe ou non (anti-énumération).
        return response()->json([
            'message' => __('Si un compte existe avec cette adresse, un lien vous a été envoyé.'),
        ]);
    }

    /**
     * GET /auth/magic-link/verify?token=…&redirect=… — flux navigateur.
     */
    public function magicLinkVerify(Request $request): RedirectResponse
    {
        $rawToken = (string) $request->query('token', '');
        $redirectTo = $this->safeRedirect((string) $request->query('redirect', ''));

        // Déjà connecté : ne consomme pas un token d'un autre compte, retour silencieux.
        if (Auth::check()) {
            $claim = MagicLink::claim($rawToken, (int) Auth::id());

            if (! $claim['ok']) {
                if ($claim['code'] === 'wrong_user') {
                    Log::warning('Magic link destiné à un autre compte (connecté #'.Auth::id().').');
                }

                return redirect($redirectTo);
            }

            if ($claim['type'] === MagicLink::TYPE_EMAIL_CHANGE) {
                CustomerService::finalizeEmailChange($request->user());

                return redirect($redirectTo.(str_contains($redirectTo, '?') ? '&' : '?').'notice=email_changed');
            }

            return redirect($redirectTo);
        }

        $claim = MagicLink::claim($rawToken);
        if (! $claim['ok']) {
            $code = in_array($claim['code'], ['invalid', 'used', 'expired'], true) ? $claim['code'] : 'invalid';

            return redirect($redirectTo.(str_contains($redirectTo, '?') ? '&' : '?').'auth_error='.$code);
        }

        $user = User::find($claim['user_id']);
        if (! $user) {
            return redirect($redirectTo.(str_contains($redirectTo, '?') ? '&' : '?').'auth_error=invalid');
        }

        // Première vérification d'email → active le compte.
        CustomerService::activate($user);

        $notice = '';
        if ($claim['type'] === MagicLink::TYPE_EMAIL_CHANGE) {
            CustomerService::finalizeEmailChange($user);
            $notice = 'email_changed';
        }

        Auth::login($user); // Session non persistante (pas de remember).
        $request->session()->regenerate();

        return redirect($redirectTo.($notice ? (str_contains($redirectTo, '?') ? '&' : '?').'notice='.$notice : ''));
    }

    /**
     * POST /auth/login — email + mot de passe.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'max:191'],
        ]);

        // Anti-bruteforce : 10/h/email + 20/h/IP.
        if (! $this->allow('login:'.strtolower($validated['email']), 10)
            || ! $this->allow('login-ip:'.$request->ip(), 20)) {
            return $this->rateLimited();
        }

        $user = User::where('email', strtolower($validated['email']))->first();

        if (! $user || $user->password === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => __('Email ou mot de passe incorrect.')], 401);
        }

        CustomerService::activate($user); // Un login réussi vaut vérification d'email.
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['message' => __('Connexion réussie.')]);
    }

    /**
     * POST /auth/logout.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('Déconnecté.')]);
    }

    /**
     * POST /auth/password/forgot.
     */
    public function passwordForgot(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:191']]);

        if (! $this->allow('forgot:'.strtolower($validated['email']), 5)
            || ! $this->allow('forgot-ip:'.$request->ip(), 10)) {
            return $this->rateLimited();
        }

        $user = CustomerService::resolveUserByEmail($validated['email']);
        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        // Réponse identique dans tous les cas (anti-énumération).
        return response()->json([
            'message' => __('Si un compte existe avec cette adresse, un email de réinitialisation a été envoyé.'),
        ]);
    }

    /**
     * POST /auth/password/reset.
     */
    public function passwordReset(Request $request): JsonResponse
    {
        if (! $this->allow('reset-ip:'.$request->ip(), 10)) {
            return $this->rateLimited();
        }

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:8', 'max:191'],
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $validated['password'],
                'token' => $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __('Ce lien de réinitialisation est invalide ou expiré.')], 403);
        }

        return response()->json(['message' => __('Mot de passe mis à jour. Vous pouvez maintenant vous connecter.')]);
    }

    /**
     * URL de redirection sûre : même origine uniquement.
     *
     * str_starts_with() sur une simple chaîne serait contournable (ex.
     * "https://site.com.evil.com" commence bien par "https://site.com") :
     * on compare host + scheme parsés, pas des préfixes de texte.
     */
    private function safeRedirect(string $url): string
    {
        if ($url === '') {
            return url('/');
        }

        $target = parse_url($url);
        $expected = parse_url(url('/'));

        if (($target['host'] ?? null) === ($expected['host'] ?? null)
            && ($target['scheme'] ?? null) === ($expected['scheme'] ?? null)) {
            return $url;
        }

        return url('/');
    }

    private function allow(string $bucket, int $maxPerHour): bool
    {
        if (RateLimiter::tooManyAttempts($bucket, $maxPerHour)) {
            return false;
        }
        RateLimiter::hit($bucket, 3600);

        return true;
    }

    private function rateLimited(): JsonResponse
    {
        return response()->json(['message' => __('Trop de demandes. Réessayez dans une heure.')], 429);
    }
}
