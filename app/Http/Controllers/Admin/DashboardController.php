<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $counts = Booking::select('status', DB::raw('COUNT(*) as n'))
            ->groupBy('status')->pluck('n', 'status')->all();
        $counts['total'] = array_sum($counts);

        // CA = réservations payées uniquement.
        $paid = fn () => Booking::where('payment_status', 'paid');

        return view('admin.dashboard', [
            'counts' => $counts,
            'revenue_today' => (float) $paid()->whereDate('created_at', today())->sum('price'),
            'revenue_week' => (float) $paid()->where('created_at', '>=', now()->subDays(7))->sum('price'),
            'revenue_month' => (float) $paid()->whereBetween('created_at', [now()->startOfMonth(), now()])->sum('price'),
            'revenue_total' => (float) $paid()->sum('price'),
            'count_vehicles' => Vehicle::where('is_active', true)->count(),
            'count_customers' => Customer::count(),
            'recent' => Booking::latest()->limit(8)->get(),
        ]);
    }
}
