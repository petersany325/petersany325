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
use App\Support\ReportSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function saveSettings(Request $request): RedirectResponse
    {
        ReportSettings::applyRequest($request);
        $redirect = $request->get('redirect');
        $appRoot = rtrim((string) config('app.url'), '/');
        if (is_string($redirect) && (str_starts_with($redirect, $appRoot.'/') || str_starts_with($redirect, '/'))) {
            return redirect()->to($redirect)->with('success', 'تنظیمات گزارش‌ها اعمال شد.');
        }

        return back()->with('success', 'تنظیمات گزارش‌ها اعمال شد.');
    }

    public function accounting(Request $request)
    {
        [$from, $to] = $this->range($request);

        return redirect()->route('accounting.index', compact('from', 'to'));
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
        $chartStatusLabels = [];
        $chartStatusValues = [];
        foreach ($byStatus as $status => $total) {
            $chartStatusLabels[] = $statusLabels[$status] ?? $status;
            $chartStatusValues[] = (int) $total;
        }

        return view('reports.operations', compact(
            'from', 'to', 'byStatus', 'statusLabels', 'intake', 'delivered',
            'openNow', 'readyUnpaid', 'waitingPart', 'avgDays', 'revenue', 'daily',
            'chartStatusLabels', 'chartStatusValues'
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

        $custodyLabels = ['front_desk' => 'نزد پذیرش', 'with_technician' => 'دست تعمیرکار', 'returning' => 'در حال بازگشت'];
        $chartCustodyLabels = [];
        $chartCustodyValues = [];
        foreach ($byCustody as $key => $total) {
            $chartCustodyLabels[] = $custodyLabels[$key] ?? $key;
            $chartCustodyValues[] = (int) $total;
        }

        return view('reports.custody', compact(
            'from', 'to', 'summary', 'byTech', 'techNames', 'inHand', 'pendingRows', 'byCustody',
            'chartCustodyLabels', 'chartCustodyValues'
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

        $chartMethodLabels = $byMethod->map(fn ($r) => Payment::METHODS[$r->method] ?? $r->method)->values()->all();
        $chartMethodValues = $byMethod->pluck('amount')->map(fn ($v) => (int) $v)->values()->all();
        $chartDailyLabels = $daily->pluck('day')->values()->all();
        $chartDailyValues = $daily->pluck('net')->map(fn ($v) => (int) $v)->values()->all();

        return view('reports.payments', compact(
            'from', 'to', 'totalIn', 'totalRefund', 'byMethod', 'byType',
            'daily', 'gateway', 'receivables', 'recent',
            'chartMethodLabels', 'chartMethodValues', 'chartDailyLabels', 'chartDailyValues'
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
            ->with(['customer', 'reception', 'costApproval'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('ok', false)
            ->latest('id')
            ->limit(30)
            ->get();

        $approvals = \App\Models\CostApproval::query()
            ->with(['reception', 'customer'])
            ->where(function ($q) use ($from, $to) {
                $q->whereDate('sent_at', '>=', $from)->whereDate('sent_at', '<=', $to);
            })
            ->latest('id')
            ->limit(40)
            ->get();

        $approvalSummary = [
            'sent' => (clone $approvals)->count(),
            'approved' => $approvals->where('status', 'approved')->count(),
            'rejected' => $approvals->where('status', 'rejected')->count(),
            'viewed' => $approvals->whereIn('status', ['viewed', 'approved', 'rejected'])->count(),
        ];

        return view('reports.sms', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'byStatus' => $byStatus,
            'fails' => $fails,
            'approvals' => $approvals,
            'approvalSummary' => $approvalSummary,
            'chartSmsLabels' => ['موفق', 'ناموفق'],
            'chartSmsValues' => [(int) $summary['ok'], (int) $summary['fail']],
        ]);
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

        return view('reports.messages', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'avgResponseHours' => $avgResponseHours,
            'rows' => $rows,
            'chartMsgLabels' => ['خوانده‌شده', 'خوانده‌نشده', 'فوری'],
            'chartMsgValues' => [(int) $summary['read'], (int) $summary['unread'], (int) $summary['urgent']],
        ]);
    }

    public function technicians(Request $request): View
    {
        [$from, $to] = $this->range($request);
        $q = trim((string) $request->get('q', ''));

        $inHandMap = Reception::query()
            ->select('custody_technician_id', DB::raw('COUNT(*) as total'))
            ->where('custody', 'with_technician')
            ->whereNotNull('custody_technician_id')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->groupBy('custody_technician_id')
            ->pluck('total', 'custody_technician_id');

        $query = Technician::query();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('specialty', 'like', "%{$q}%");
            });
        }

        $rows = $query->withCount([
            'receptions as jobs_count' => function ($q2) use ($from, $to) {
                $q2->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
            'receptions as delivered_count' => function ($q2) use ($from, $to) {
                $q2->where('status', 'delivered')
                    ->whereDate('delivered_at', '>=', $from)
                    ->whereDate('delivered_at', '<=', $to);
            },
        ])
            ->withSum([
                'receptions as labor_sum' => function ($q2) use ($from, $to) {
                    $q2->where('status', 'delivered')
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

        $chartLabels = $rows->pluck('name')->values()->all();
        $chartJobs = $rows->pluck('jobs_count')->map(fn ($v) => (int) $v)->values()->all();
        $chartLabor = $rows->pluck('labor_sum')->map(fn ($v) => (int) $v)->values()->all();

        return view('reports.technicians', compact(
            'rows', 'from', 'to', 'q', 'chartLabels', 'chartJobs', 'chartLabor'
        ));
    }

    public function technicianShow(Request $request, Technician $technician): View
    {
        [$from, $to] = $this->range($request);

        $jobs = Reception::query()
            ->with(['customer', 'parts', 'payments'])
            ->where('technician_id', $technician->id)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->latest('id')
            ->get();

        $delivered = $jobs->where('status', 'delivered');
        $laborSum = (int) $delivered->sum('labor_cost');
        $partsSum = (int) $delivered->sum('parts_cost');
        $commissionSum = (int) round($laborSum * ((float) $technician->commission_percent) / 100);

        $inHand = Reception::query()
            ->with('customer')
            ->where('custody', 'with_technician')
            ->where('custody_technician_id', $technician->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest('id')
            ->get();

        $partsUsed = ReceptionPart::query()
            ->select('part_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total_price) as amount'))
            ->whereHas('reception', function ($q) use ($technician, $from, $to) {
                $q->where('technician_id', $technician->id)
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
            })
            ->groupBy('part_name')
            ->orderByDesc('qty')
            ->get();

        $handoffs = DeviceHandoff::query()
            ->where('to_technician_id', $technician->id)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = $jobs->groupBy('status')->map->count();
        $statusLabels = Reception::availableStatuses();
        $chartStatusLabels = [];
        $chartStatusValues = [];
        foreach ($byStatus as $status => $count) {
            $chartStatusLabels[] = $statusLabels[$status] ?? $status;
            $chartStatusValues[] = (int) $count;
        }

        $daily = $jobs->groupBy(fn ($r) => optional($r->created_at)->toDateString())
            ->map->count()
            ->sortKeys();

        $avgDays = $delivered
            ->filter(fn ($r) => $r->received_at && $r->delivered_at)
            ->avg(fn ($r) => $r->received_at->diffInHours($r->delivered_at) / 24);

        return view('reports.technician-show', compact(
            'technician', 'from', 'to', 'jobs', 'delivered', 'laborSum', 'partsSum',
            'commissionSum', 'inHand', 'partsUsed', 'handoffs', 'byStatus', 'statusLabels',
            'chartStatusLabels', 'chartStatusValues', 'daily', 'avgDays'
        ));
    }

    public function customers(Request $request): View
    {
        [$from, $to] = $this->range($request);
        $q = trim((string) $request->get('q', ''));

        $searchResults = collect();
        if ($q !== '') {
            $searchResults = Customer::query()
                ->with('referralSource')
                ->withCount('receptions')
                ->withSum('receptions', 'total_amount')
                ->withSum('receptions', 'paid_amount')
                ->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('national_code', 'like', "%{$q}%");
                })
                ->orderBy('name')
                ->limit(40)
                ->get()
                ->map(function (Customer $c) {
                    $billed = (int) ($c->receptions_sum_total_amount ?? 0);
                    $paid = (int) ($c->receptions_sum_paid_amount ?? 0);
                    $c->billed_sum = $billed;
                    $c->paid_sum = $paid;
                    $c->debt_sum = max(0, $billed - $paid);

                    return $c;
                });
        }

        $topCustomers = Customer::withCount([
            'receptions as visits' => function ($q2) use ($from, $to) {
                $q2->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
        ])
            ->withSum([
                'receptions as paid_sum' => function ($q2) use ($from, $to) {
                    $q2->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
                },
            ], 'paid_amount')
            ->having('visits', '>', 0)
            ->orderByDesc('visits')
            ->limit(20)
            ->get()
            ->each(function (Customer $c) {
                $c->paid_sum = (int) ($c->paid_sum ?? 0);
            });

        $referrals = ReferralSource::withCount([
            'customers as customers_count' => function ($q2) use ($from, $to) {
                $q2->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
        ])->orderByDesc('customers_count')->get();

        $faults = FaultType::withCount([
            'receptions as jobs' => function ($q2) use ($from, $to) {
                $q2->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            },
        ])->orderByDesc('jobs')->get();

        $chartRefLabels = $referrals->pluck('name')->values()->all();
        $chartRefValues = $referrals->pluck('customers_count')->map(fn ($v) => (int) $v)->values()->all();
        $chartTopLabels = $topCustomers->take(8)->pluck('name')->values()->all();
        $chartTopValues = $topCustomers->take(8)->pluck('visits')->map(fn ($v) => (int) $v)->values()->all();

        return view('reports.customers', compact(
            'topCustomers', 'referrals', 'faults', 'from', 'to', 'q', 'searchResults',
            'chartRefLabels', 'chartRefValues', 'chartTopLabels', 'chartTopValues'
        ));
    }

    public function customerShow(Request $request, Customer $customer): View
    {
        [$from, $to] = $this->range($request);

        $customer->load('referralSource');

        $receptions = Reception::query()
            ->with(['technician', 'custodyTechnician', 'faultType', 'parts', 'payments', 'handoffs.toTechnician'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->get();

        $periodReceptions = $receptions->filter(function (Reception $r) use ($from, $to) {
            $day = optional($r->created_at)->toDateString();

            return $day && $day >= $from && $day <= $to;
        });

        $lifetime = [
            'tickets' => $receptions->count(),
            'open' => $receptions->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'delivered' => $receptions->where('status', 'delivered')->count(),
            'billed' => (int) $receptions->sum('total_amount'),
            'paid' => (int) $receptions->sum('paid_amount'),
            'parts_qty' => (int) $receptions->sum(fn ($r) => $r->parts->sum('quantity')),
            'parts_amount' => (int) $receptions->sum(fn ($r) => $r->parts->sum('total_price')),
            'first_visit' => $receptions->min('received_at') ?? $receptions->min('created_at'),
            'last_visit' => $receptions->max('received_at') ?? $receptions->max('created_at'),
        ];
        $lifetime['debt'] = max(0, $lifetime['billed'] - $lifetime['paid']);

        $period = [
            'tickets' => $periodReceptions->count(),
            'billed' => (int) $periodReceptions->sum('total_amount'),
            'paid' => (int) $periodReceptions->sum('paid_amount'),
            'parts_qty' => (int) $periodReceptions->sum(fn ($r) => $r->parts->sum('quantity')),
        ];
        $period['debt'] = max(0, $period['billed'] - $period['paid']);

        $payments = Payment::query()
            ->with('reception')
            ->where('customer_id', $customer->id)
            ->latest('paid_at')
            ->get();

        $messages = CustomerMessage::query()
            ->with('reception')
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->limit(30)
            ->get();

        $smsLogs = SmsLog::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->limit(20)
            ->get();

        $byStatus = $receptions->groupBy('status')->map->count();
        $statusLabels = Reception::availableStatuses();
        $chartStatusLabels = [];
        $chartStatusValues = [];
        foreach ($byStatus as $status => $count) {
            $chartStatusLabels[] = $statusLabels[$status] ?? $status;
            $chartStatusValues[] = (int) $count;
        }

        $payDaily = $payments
            ->filter(fn ($p) => $p->paid_at)
            ->groupBy(fn ($p) => $p->paid_at->toDateString())
            ->map(fn ($g) => (int) $g->sum('amount'))
            ->sortKeys();

        return view('reports.customer-show', compact(
            'customer', 'from', 'to', 'receptions', 'periodReceptions', 'lifetime', 'period',
            'payments', 'messages', 'smsLogs', 'byStatus', 'statusLabels',
            'chartStatusLabels', 'chartStatusValues', 'payDaily'
        ));
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

        return view('reports.parts-used', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'chartPartLabels' => $rows->take(10)->pluck('part_name')->values()->all(),
            'chartPartValues' => $rows->take(10)->pluck('qty')->map(fn ($v) => (int) $v)->values()->all(),
        ]);
    }

    /** @return array{0:string,1:string} */
    private function range(Request $request): array
    {
        ReportSettings::syncFromQuery($request);

        return ReportSettings::range();
    }
}
