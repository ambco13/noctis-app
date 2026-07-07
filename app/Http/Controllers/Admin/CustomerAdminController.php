<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\View\View;

class CustomerAdminController extends Controller
{
    public function index(): View
    {
        // withCount : sous-requête compatible ONLY_FULL_GROUP_BY (le join+groupBy
        // plantait sur MariaDB en mode strict).
        $customers = Customer::withCount('bookings as rides')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.customers', ['customers' => $customers]);
    }
}
