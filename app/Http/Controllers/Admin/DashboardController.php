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
            'chart_revenue' => $this->dailySeries(true),
            'chart_bookings' => $this->dailySeries(false),
        ]);
    }

    /**
     * Série quotidienne sur 30 jours : CA payé ou nombre de réservations.
     *
     * @return array<string, float> date Y-m-d → valeur (jours vides à 0).
     */
    private function dailySeries(bool $revenue): array
    {
        $from = now()->subDays(29)->startOfDay();

        $query = Booking::where('created_at', '>=', $from)
            ->selectRaw($revenue
                ? 'DATE(created_at) as d, SUM(price) as v'
                : 'DATE(created_at) as d, COUNT(*) as v')
            ->groupBy('d');

        if ($revenue) {
            $query->where('payment_status', 'paid');
        }

        $byDay = $query->pluck('v', 'd');

        $out = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $out[$date] = (float) ($byDay[$date] ?? 0);
        }

        return $out;
    }
}
