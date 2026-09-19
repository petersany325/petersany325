<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user && $user->isIntern()) {
            return redirect()->route('intern.portal');
        }

        $openStatuses = ['received', 'repairing', 'waiting_part', 'ready'];

        return view('dashboard', [
            'openCount' => Reception::whereIn('status', $openStatuses)->count(),
            'readyCount' => Reception::where('status', 'ready')->count(),
            'customersCount' => Customer::count(),
            'lowStockCount' => Part::whereColumn('stock', '<=', 'min_stock')->where('is_active', true)->count(),
            'todayIncome' => Payment::whereDate('paid_at', today())->sum('amount'),
            'techniciansCount' => Technician::where('is_active', true)->count(),
            'recentReceptions' => Reception::with(['customer', 'technician'])
                ->latest()
                ->limit(8)
                ->get(),
            'statusBreakdown' => Reception::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }
}
