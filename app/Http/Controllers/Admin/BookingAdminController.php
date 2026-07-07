<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::query()->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($s = trim((string) $request->query('s'))) {
            $query->where(function ($q) use ($s) {
                $q->where('booking_ref', 'like', "%{$s}%")
                    ->orWhere('full_name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        return view('admin.bookings', [
            'bookings' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            's' => $s ?? '',
        ]);
    }

    public function show(Booking $booking): View
    {
        return view('admin.booking-detail', ['booking' => $booking]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled,failed'],
        ]);

        $booking->update(['status' => $validated['status']]);

        return back()->with('ok', __('Statut mis à jour.'));
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('admin.bookings')->with('ok', __('Réservation supprimée.'));
    }
}
