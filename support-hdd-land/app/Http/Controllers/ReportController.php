<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FaultType;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\ReceptionPart;
use App\Models\ReferralSource;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function accounting(Request $request)
    {
        return redirect()->route('accounting.index', $request->only(['from', 'to']));
    }

    public function technicians(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $rows = Technician::withCount([
            'receptions as jobs_count' => function ($q) use ($from, $to) {
                $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
            'receptions as delivered_count' => function ($q) use ($from, $to) {
                $q->where('status', 'delivered')
                    ->whereDate('delivered_at', '>=', $from)
                    ->whereDate('delivered_at', '<=', $to);
            },
        ])
            ->withSum([
                'receptions as labor_sum' => function ($q) use ($from, $to) {
                    $q->where('status', 'delivered')
                        ->whereDate('delivered_at', '>=', $from)
                        ->whereDate('delivered_at', '<=', $to);
                },
            ], 'labor_cost')
            ->orderBy('name')
            ->get();

        return view('reports.technicians', compact('rows', 'from', 'to'));
    }

    public function customers(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $topCustomers = Customer::withCount([
            'receptions as visits' => function ($q) use ($from, $to) {
                $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
        ])
            ->having('visits', '>', 0)
            ->orderByDesc('visits')
            ->limit(20)
            ->get();

        $referrals = ReferralSource::withCount([
            'customers as customers_count' => function ($q) use ($from, $to) {
                $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
        ])->orderByDesc('customers_count')->get();

        $faults = FaultType::withCount([
            'receptions as jobs' => function ($q) use ($from, $to) {
                $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
        ])->orderByDesc('jobs')->get();

        return view('reports.customers', compact('topCustomers', 'referrals', 'faults', 'from', 'to'));
    }

    public function partsUsed(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $rows = ReceptionPart::query()
            ->select('part_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total_price) as amount'))
            ->whereDate('used_at', '>=', $from)
            ->whereDate('used_at', '<=', $to)
            ->groupBy('part_name')
            ->orderByDesc('qty')
            ->get();

        return view('reports.parts-used', compact('rows', 'from', 'to'));
    }
}
