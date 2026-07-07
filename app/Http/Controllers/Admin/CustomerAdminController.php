<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerAdminController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->leftJoin('bookings', 'bookings.customer_id', '=', 'customers.id')
            ->select('customers.*')
            ->addSelect(DB::raw('COUNT(bookings.id) as rides'))
            ->groupBy('customers.id')
            ->orderByDesc('customers.created_at')
            ->paginate(25);

        return view('admin.customers', ['customers' => $customers]);
    }
}
