<?php

namespace App\Http\Controllers;

use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page /mon-compte : formulaire d'authentification hybride (visiteur)
 * ou dashboard à onglets réservations / profil (connecté).
 */
class AccountPageController extends Controller
{
    public function show(Request $request): View
    {
        if (! $request->user()) {
            return view('account.index', ['tab' => null]);
        }

        $tab = $request->query('tab', 'bookings');
        if (! in_array($tab, ['bookings', 'profile'], true)) {
            $tab = 'bookings';
        }

        return view('account.index', [
            'tab' => $tab,
            'bookings' => $tab === 'bookings' ? CustomerService::getBookings($request->user()) : null,
        ]);
    }
}
