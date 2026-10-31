<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\Technician;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user && $user->isIntern()) {
            return redirect()->route('intern.portal');
        }

        $stats = Cache::remember('dashboard_stats_v2', 30, function () {
            $openStatuses = ['received', 'repairing', 'waiting_part', 'ready'];
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();

            return [
                'openCount' => Reception::whereIn('status', $openStatuses)->count(),
                'readyCount' => Reception::where('status', 'ready')->count(),
                'customersCount' => Customer::count(),
                'lowStockCount' => Part::whereColumn('stock', '<=', 'min_stock')->where('is_active', true)->count(),
                'todayIncome' => Payment::whereBetween('paid_at', [$todayStart, $todayEnd])->sum('amount'),
                'techniciansCount' => Technician::where('is_active', true)->count(),
                'statusBreakdown' => Reception::select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status'),
            ];
        });

        return view('dashboard', array_merge($stats, [
            'recentReceptions' => Reception::with(['customer:id,name', 'technician:id,name'])
                ->latest('id')
                ->limit(8)
                ->get(['id', 'ticket_no', 'receipt_no', 'status', 'product_name', 'customer_id', 'technician_id', 'created_at']),
        ]));
    }
}
