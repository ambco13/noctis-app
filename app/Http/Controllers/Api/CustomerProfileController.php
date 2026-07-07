<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Profil du client connecté : lecture/édition, changement d'email confirmé,
 * mot de passe, réservations, export et suppression RGPD.
 */
class CustomerProfileController extends Controller
{
    /**
     * GET /customer/me.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'status' => $user->account_status,
            'has_password' => $user->password !== null,
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
        ]);
    }

    /**
     * PATCH /customer/me.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $request->user();
        $user->fill(array_filter($validated, fn ($v) => $v !== null));
        $user->name = trim($user->first_name.' '.$user->last_name) ?: $user->name;
        $user->save();

        return response()->json(['message' => __('Profil mis à jour.')]);
    }

    /**
     * POST /customer/me/email — demande de changement, confirmé par magic link.
     */
    public function requestEmailChange(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:191']]);

        $result = CustomerService::requestEmailChange($request->user(), $validated['email']);

        if ($result !== true) {
            $status = match ($result) {
                'exists' => 409,
                'invalid_email', 'same_email' => 400,
                default => 500,
            };
            $messages = [
                'exists' => __('Cette adresse est déjà associée à un compte.'),
                'invalid_email' => __('Adresse email invalide.'),
                'same_email' => __('Cette adresse est déjà la vôtre.'),
                'send_failed' => __("Erreur lors de l'envoi de l'email."),
            ];

            return response()->json(['message' => $messages[$result] ?? $messages['send_failed']], $status);
        }

        return response()->json([
            'message' => __('Un lien de confirmation a été envoyé à votre nouvelle adresse.'),
        ]);
    }

    /**
     * POST /customer/me/password — définit / change le mot de passe.
     */
    public function setPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['password' => ['required', 'string', 'min:8', 'max:191']]);

        $request->user()->forceFill(['password' => Hash::make($validated['password'])])->save();

        return response()->json(['message' => __('Mot de passe enregistré.')]);
    }

    /**
     * GET /customer/bookings.
     */
    public function bookings(Request $request): JsonResponse
    {
        return response()->json(CustomerService::getBookings($request->user()));
    }

    /**
     * GET /customer/me/export — export RGPD (téléchargement JSON).
     */
    public function export(Request $request)
    {
        return response()->json(CustomerService::exportData($request->user()))
            ->header('Content-Disposition', 'attachment; filename="mes-donnees.json"');
    }

    /**
     * POST /customer/me/delete — suppression (anonymisation RGPD).
     */
    public function destroy(Request $request): JsonResponse
    {
        $result = CustomerService::deleteAccount($request->user());

        if ($result !== true) {
            // Garde : réservations futures confirmées → 409 + liste.
            return response()->json([
                'code' => 'future_bookings',
                'message' => __('Impossible de supprimer le compte : des réservations à venir sont confirmées.'),
                'bookings' => $result,
            ], 409);
        }

        // Pas de Auth::logout() ici : il recycle le remember token via un save()
        // qui ré-insérerait l'utilisateur fraîchement supprimé (exists = false).
        Auth::guard()->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('Compte supprimé.')]);
    }
}
