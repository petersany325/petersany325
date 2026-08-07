<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\DeviceHandoff;
use App\Models\FaultType;
use App\Models\GatewayTransaction;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\ReceptionPart;
use App\Models\ReferralSource;
use App\Models\SmsLog;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function accounting(Request $request)
    {
        return redirect()->route('accounting.index', $request->only(['from', 'to']));
    }

    public function operations(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $base = Reception::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $byStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $intake = (clone $base)->count();
        $delivered = Reception::query()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->count();

        $openNow = Reception::query()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        $readyUnpaid = Reception::query()
            ->where('status', 'ready')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->count();

        $waitingPart = Reception::query()->where('status', 'waiting_part')->count();

        $avgDays = Reception::query()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->whereNotNull('received_at')
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, received_at, delivered_at)) / 24 as avg_days')
            ->value('avg_days');

        $revenue = Reception::query()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->selectRaw('COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(labor_cost),0) as labor, COALESCE(SUM(parts_cost),0) as parts, COALESCE(SUM(discount),0) as discount')
            ->first();

        $daily = Reception::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $statusLabels = Reception::availableStatuses();

        return view('reports.operations', compact(
            'from', 'to', 'byStatus', 'statusLabels', 'intake', 'delivered',
            'openNow', 'readyUnpaid', 'waitingPart', 'avgDays', 'revenue', 'daily'
        ));
    }

    public function custody(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $handoffs = DeviceHandoff::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $summary = [
            'total' => (clone $handoffs)->count(),
            'pending' => (clone $handoffs)->where('status', DeviceHandoff::STATUS_PENDING)->count(),
            'accepted' => (clone $handoffs)->where('status', DeviceHandoff::STATUS_ACCEPTED)->count(),
            'rejected' => (clone $handoffs)->where('status', DeviceHandoff::STATUS_REJECTED)->count(),
            'to_bench' => (clone $handoffs)->where('direction', DeviceHandoff::DIR_TO_BENCH)->count(),
            'to_front' => (clone $handoffs)->where('direction', DeviceHandoff::DIR_TO_FRONT)->count(),
        ];

        $byTech = DeviceHandoff::query()
            ->select('to_technician_id', 'status', DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereNotNull('to_technician_id')
            ->groupBy('to_technician_id', 'status')
            ->get()
            ->groupBy('to_technician_id');

        $techNames = Technician::query()->pluck('name', 'id');

        $inHand = Reception::query()
            ->with(['customer', 'custodyTechnician'])
            ->where('custody', 'with_technician')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest('id')
            ->limit(50)
            ->get();

        $pendingRows = DeviceHandoff::query()
            ->with(['reception.customer', 'toTechnician', 'fromUser'])
            ->where('status', DeviceHandoff::STATUS_PENDING)
            ->latest('id')
            ->limit(30)
            ->get();

        $byCustody = Reception::query()
            ->select('custody', DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->groupBy('custody')
            ->pluck('total', 'custody');

        return view('reports.custody', compact(
            'from', 'to', 'summary', 'byTech', 'techNames', 'inHand', 'pendingRows', 'byCustody'
        ));
    }

    public function payments(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $payments = Payment::query()
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to);

        $totalIn = (clone $payments)->where('type', '!=', 'refund')->sum('amount');
        $totalRefund = (clone $payments)->where('type', 'refund')->sum('amount');

        $byMethod = (clone $payments)
            ->select('method', DB::raw('SUM(amount) as amount'), DB::raw('COUNT(*) as total'))
            ->groupBy('method')
            ->orderByDesc('amount')
            ->get();

        $byType = (clone $payments)
            ->select('type', DB::raw('SUM(amount) as amount'), DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('amount')
            ->get();

        $daily = (clone $payments)
            ->selectRaw('DATE(paid_at) as day, SUM(CASE WHEN type = "refund" THEN -amount ELSE amount END) as net, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $gateway = GatewayTransaction::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(amount),0) as amount'))
            ->groupBy('status')
            ->get();

        $receivables = Reception::query()
            ->with('customer')
            ->whereNotIn('status', ['cancelled'])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->orderByRaw('(total_amount - paid_amount) DESC')
            ->limit(25)
            ->get();

        $recent = Payment::query()
            ->with(['customer', 'reception', 'receiver'])
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->latest('paid_at')
            ->limit(40)
            ->get();

        return view('reports.payments', compact(
            'from', 'to', 'totalIn', 'totalRefund', 'byMethod', 'byType',
            'daily', 'gateway', 'receivables', 'recent'
        ));
    }

    public function sms(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $logs = SmsLog::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $summary = [
            'total' => (clone $logs)->count(),
            'ok' => (clone $logs)->where('ok', true)->count(),
            'fail' => (clone $logs)->where('ok', false)->count(),
            'customer' => (clone $logs)->where('audience', 'customer')->count(),
            'coworker' => (clone $logs)->where('audience', 'coworker')->count(),
        ];

        $byStatus = (clone $logs)
            ->select('status_key', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN ok = 1 THEN 1 ELSE 0 END) as ok_count'))
            ->groupBy('status_key')
            ->orderByDesc('total')
            ->get();

        $fails = SmsLog::query()
            ->with(['customer', 'reception'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('ok', false)
            ->latest('id')
            ->limit(30)
            ->get();

        return view('reports.sms', compact('from', 'to', 'summary', 'byStatus', 'fails'));
    }

    public function messages(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $base = CustomerMessage::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $summary = [
            'total' => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('staff_read_at')->count(),
            'urgent' => (clone $base)->where('priority', 'urgent')->count(),
            'read' => (clone $base)->whereNotNull('staff_read_at')->count(),
        ];

        $avgResponseHours = CustomerMessage::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereNotNull('staff_read_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, staff_read_at)) / 60 as avg_h')
            ->value('avg_h');

        $rows = CustomerMessage::query()
            ->with(['customer', 'reception', 'handler'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->latest('id')
            ->limit(50)
            ->get();

        return view('reports.messages', compact('from', 'to', 'summary', 'avgResponseHours', 'rows'));
    }

    public function technicians(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $inHandMap = Reception::query()
            ->select('custody_technician_id', DB::raw('COUNT(*) as total'))
            ->where('custody', 'with_technician')
            ->whereNotNull('custody_technician_id')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->groupBy('custody_technician_id')
            ->pluck('total', 'custody_technician_id');

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
            ->get()
            ->map(function (Technician $row) use ($inHandMap) {
                $labor = (int) ($row->labor_sum ?? 0);
                $pct = (float) ($row->commission_percent ?? 0);
                $row->commission_sum = (int) round($labor * $pct / 100);
                $row->in_hand_count = (int) ($inHandMap[$row->id] ?? 0);

                return $row;
            });

        return view('reports.technicians', compact('rows', 'from', 'to'));
    }

    public function customers(Request $request): View
    {
        [$from, $to] = $this->range($request);

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

    public function partsUsed(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $rows = ReceptionPart::query()
            ->select('part_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total_price) as amount'))
            ->whereDate('used_at', '>=', $from)
            ->whereDate('used_at', '<=', $to)
            ->groupBy('part_name')
            ->orderByDesc('qty')
            ->get();

        return view('reports.parts-used', compact('rows', 'from', 'to'));
    }

    /** @return array{0:string,1:string} */
    private function range(Request $request): array
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return [(string) $from, (string) $to];
    }
}
