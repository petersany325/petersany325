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
use App\Models\StockMovement;
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
            // Drop stale ?period=&from=&to= so syncFromQuery cannot overwrite the new session range.
            $parts = parse_url($redirect);
            $path = ($parts['path'] ?? '/');
            $target = (str_starts_with($redirect, 'http') ? ($appRoot.$path) : $path);

            return redirect()->to($target)->with('success', 'تنظیمات گزارش‌ها اعمال شد.');
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
        $ticket = normalize_receipt_search_query((string) $request->input('ticket_no', ''));
        $serial = trim((string) $request->input('serial', ''));
        $q = normalize_receipt_search_query((string) $request->input('q', ''));

        $handoffs = DeviceHandoff::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $this->applyCustodySearch($handoffs, $ticket, $serial, $q);

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

        $inHandQuery = Reception::query()
            ->with(['customer', 'custodyTechnician'])
            ->where('custody', 'with_technician')
            ->whereNotIn('status', ['delivered', 'cancelled']);
        if ($ticket !== '') {
            $inHandQuery->where(function ($w) use ($ticket) {
                $w->where('ticket_no', 'like', '%'.$ticket.'%')
                    ->orWhere('receipt_no', 'like', '%'.$ticket.'%');
            });
        }
        if ($serial !== '') {
            $inHandQuery->where('serial_number', 'like', '%'.$serial.'%');
        }
        if ($q !== '') {
            $inHandQuery->where(function ($inner) use ($q) {
                $inner->where('ticket_no', 'like', '%'.$q.'%')
                    ->orWhere('receipt_no', 'like', '%'.$q.'%')
                    ->orWhere('serial_number', 'like', '%'.$q.'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$q.'%'));
            });
        }
        $inHand = $inHandQuery->latest('id')->limit(80)->get();

        $pendingRowsQuery = DeviceHandoff::query()
            ->with(['reception.customer', 'toTechnician', 'fromUser'])
            ->where('status', DeviceHandoff::STATUS_PENDING);
        $this->applyCustodySearch($pendingRowsQuery, $ticket, $serial, $q);
        $pendingRows = $pendingRowsQuery->latest('id')->limit(40)->get();

        $historyQuery = DeviceHandoff::query()
            ->with(['reception.customer', 'toTechnician', 'fromUser'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereIn('status', [DeviceHandoff::STATUS_ACCEPTED, DeviceHandoff::STATUS_REJECTED]);
        $this->applyCustodySearch($historyQuery, $ticket, $serial, $q);
        $historyRows = $historyQuery->latest('id')->limit(80)->get();

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
            'chartCustodyLabels', 'chartCustodyValues', 'ticket', 'serial', 'q', 'historyRows'
        ));
    }

    private function applyCustodySearch($query, string $ticket, string $serial, string $q): void
    {
        if ($ticket !== '') {
            $query->whereHas('reception', function ($r) use ($ticket) {
                $r->where('ticket_no', 'like', '%'.$ticket.'%')
                    ->orWhere('receipt_no', 'like', '%'.$ticket.'%');
            });
        }
        if ($serial !== '') {
            $query->where(function ($inner) use ($serial) {
                $inner->where('serial_snapshot', 'like', '%'.$serial.'%')
                    ->orWhereHas('reception', fn ($r) => $r->where('serial_number', 'like', '%'.$serial.'%'));
            });
        }
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('serial_snapshot', 'like', '%'.$q.'%')
                    ->orWhereHas('reception', function ($r) use ($q) {
                        $r->where('ticket_no', 'like', '%'.$q.'%')
                            ->orWhere('receipt_no', 'like', '%'.$q.'%')
                            ->orWhere('serial_number', 'like', '%'.$q.'%')
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%'));
                    });
            });
        }
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
        $chartDailyLabels = jalali_day_labels($daily->pluck('day')->values()->all());
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

        $receptionId = $request->integer('reception_id') ?: null;
        $q = normalize_receipt_search_query((string) $request->get('q'));
        $okFilter = $request->get('ok'); // '', '1', '0'
        $audience = (string) $request->get('audience', '');
        $statusKey = trim((string) $request->get('status_key', ''));

        $logsBase = SmsLog::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->when($receptionId, fn ($query) => $query->where('reception_id', $receptionId))
            ->when($audience !== '', fn ($query) => $query->where('audience', $audience))
            ->when($statusKey !== '', fn ($query) => $query->where('status_key', $statusKey))
            ->when($okFilter === '1', fn ($query) => $query->where('ok', true))
            ->when($okFilter === '0', fn ($query) => $query->where('ok', false))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('phone', 'like', "%{$q}%")
                        ->orWhere('message', 'like', "%{$q}%")
                        ->orWhere('provider_message', 'like', "%{$q}%")
                        ->orWhereHas('reception', fn ($r) => $r->where('ticket_no', 'like', "%{$q}%")->orWhere('receipt_no', 'like', "%{$q}%"))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
                });
            });

        $summary = [
            'total' => (clone $logsBase)->count(),
            'ok' => (clone $logsBase)->where('ok', true)->count(),
            'fail' => (clone $logsBase)->where('ok', false)->count(),
            'customer' => (clone $logsBase)->where('audience', 'customer')->count(),
            'coworker' => (clone $logsBase)->where('audience', 'coworker')->count(),
        ];

        $byStatus = (clone $logsBase)
            ->select('status_key', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN ok = 1 THEN 1 ELSE 0 END) as ok_count'))
            ->groupBy('status_key')
            ->orderByDesc('total')
            ->get();

        $fails = (clone $logsBase)
            ->with(['customer', 'reception', 'costApproval'])
            ->where('ok', false)
            ->latest('id')
            ->limit(30)
            ->get();

        $entries = (clone $logsBase)
            ->with(['customer', 'reception', 'rule', 'sender', 'costApproval'])
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        $reception = $receptionId
            ? \App\Models\Reception::query()->with('customer')->find($receptionId)
            : null;

        $statusKeys = SmsLog::query()
            ->whereNotNull('status_key')
            ->where('status_key', '!=', '')
            ->distinct()
            ->orderBy('status_key')
            ->pluck('status_key');

        $approvalsQuery = \App\Models\CostApproval::query()
            ->where(function ($q) use ($from, $to) {
                $q->whereDate('sent_at', '>=', $from)->whereDate('sent_at', '<=', $to);
            })
            ->when($receptionId, fn ($query) => $query->where('reception_id', $receptionId));

        $approvalSummary = [
            'sent' => (clone $approvalsQuery)->count(),
            'approved' => (clone $approvalsQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $approvalsQuery)->where('status', 'rejected')->count(),
            'viewed' => (clone $approvalsQuery)->whereIn('status', ['viewed', 'approved', 'rejected'])->count(),
        ];

        $approvals = (clone $approvalsQuery)
            ->with(['reception', 'customer'])
            ->latest('id')
            ->limit(40)
            ->get();

        return view('reports.sms', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'byStatus' => $byStatus,
            'fails' => $fails,
            'entries' => $entries,
            'approvals' => $approvals,
            'approvalSummary' => $approvalSummary,
            'reception' => $reception,
            'receptionId' => $receptionId,
            'q' => $q,
            'okFilter' => $okFilter,
            'audience' => $audience,
            'statusKey' => $statusKey,
            'statusKeys' => $statusKeys,
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

        // Ready repairs not yet exited: current backlog amount (labor + parts).
        $pendingExitMap = Reception::query()
            ->select(
                'technician_id',
                DB::raw('COALESCE(SUM(COALESCE(labor_cost,0) + COALESCE(parts_cost,0)),0) as total')
            )
            ->where('status', 'ready')
            ->whereNotNull('technician_id')
            ->groupBy('technician_id')
            ->pluck('total', 'technician_id');

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
            ->withSum([
                'receptions as parts_sum' => function ($q2) use ($from, $to) {
                    $q2->where('status', 'delivered')
                        ->whereDate('delivered_at', '>=', $from)
                        ->whereDate('delivered_at', '<=', $to);
                },
            ], 'parts_cost')
            ->orderBy('name')
            ->get()
            ->map(function (Technician $row) use ($inHandMap, $pendingExitMap) {
                $labor = (int) ($row->labor_sum ?? 0);
                $pct = (float) ($row->commission_percent ?? 0);
                $row->labor_sum = $labor;
                $row->parts_sum = (int) ($row->parts_sum ?? 0);
                $row->commission_sum = (int) round($labor * $pct / 100);
                $row->in_hand_count = (int) ($inHandMap[$row->id] ?? 0);
                $row->pending_exit_sum = (int) ($pendingExitMap[$row->id] ?? 0);

                return $row;
            });

        $totals = [
            'labor' => (int) $rows->sum('labor_sum'),
            'parts' => (int) $rows->sum('parts_sum'),
            'commission' => (int) $rows->sum('commission_sum'),
            'pending_exit' => (int) $rows->sum('pending_exit_sum'),
        ];

        $chartLabels = $rows->pluck('name')->values()->all();
        $chartJobs = $rows->pluck('jobs_count')->map(fn ($v) => (int) $v)->values()->all();
        $chartLabor = $rows->pluck('labor_sum')->map(fn ($v) => (int) $v)->values()->all();

        return view('reports.technicians', compact(
            'rows', 'from', 'to', 'q', 'totals',
            'chartLabels', 'chartJobs', 'chartLabor'
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
        $tz = config('app.timezone', 'Asia/Tehran');
        $fromStart = \Illuminate\Support\Carbon::parse($from, $tz)->startOfDay();
        $toEnd = \Illuminate\Support\Carbon::parse($to, $tz)->endOfDay();

        // 1) Ticket exits (تحویل)
        $exits = Reception::query()
            ->with(['customer', 'technician', 'parts'])
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$fromStart, $toEnd])
            ->orderByDesc('delivered_at')
            ->get();

        $exitTotals = [
            'count' => $exits->count(),
            'labor' => (int) $exits->sum('labor_cost'),
            'parts' => (int) $exits->sum('parts_cost'),
            'total' => (int) $exits->sum('total_amount'),
            'paid_on_tickets' => (int) $exits->sum('paid_amount'),
            'remaining' => (int) $exits->sum(fn (Reception $r) => max(0, (int) $r->total_amount - (int) $r->paid_amount)),
        ];

        $exitDaily = $exits
            ->groupBy(fn (Reception $r) => optional($r->delivered_at)->timezone($tz)->toDateString())
            ->map(fn ($g) => (int) $g->sum('total_amount'))
            ->sortKeys();

        // 2) Cash desk for the same period (all payments received)
        $payments = Payment::query()
            ->with(['reception', 'customer'])
            ->whereBetween('paid_at', [$fromStart, $toEnd])
            ->orderByDesc('paid_at')
            ->get();

        $payIn = $payments->filter(fn (Payment $p) => ($p->type ?? '') !== 'refund');
        $payRefund = $payments->filter(fn (Payment $p) => ($p->type ?? '') === 'refund');
        $payByMethod = $payIn->groupBy('method')->map(fn ($g) => (int) $g->sum('amount'));
        $cashTotals = [
            'count' => $payments->count(),
            'in' => (int) $payIn->sum('amount'),
            'refund' => (int) $payRefund->sum('amount'),
            'net' => (int) $payIn->sum('amount') - (int) $payRefund->sum('amount'),
            'cash' => (int) ($payByMethod['cash'] ?? 0),
            'card' => (int) ($payByMethod['card'] ?? 0),
            'transfer' => (int) ($payByMethod['transfer'] ?? 0),
            'zarinpal' => (int) ($payByMethod['zarinpal'] ?? 0),
            'by_method' => $payByMethod,
        ];

        // 3) Warehouse stock outs
        $movements = StockMovement::query()
            ->with(['part', 'reception', 'user', 'warehouse'])
            ->where('type', 'out')
            ->whereBetween('created_at', [$fromStart, $toEnd])
            ->orderByDesc('id')
            ->get();

        $rows = $movements
            ->groupBy(fn (StockMovement $m) => $m->part?->name ?: ('قطعه #'.($m->part_id ?: '—')))
            ->map(function ($group, $name) {
                return (object) [
                    'part_name' => $name,
                    'qty' => (int) $group->sum(fn (StockMovement $m) => abs((int) $m->quantity)),
                    'amount' => (int) $group->sum(fn (StockMovement $m) => abs((int) $m->total_cost)),
                    'docs' => $group->count(),
                ];
            })
            ->sortByDesc('qty')
            ->values();

        $ticketParts = ReceptionPart::query()
            ->with('reception')
            ->where(function ($q) {
                $q->whereNull('part_id')->orWhere('part_id', 0);
            })
            ->where(function ($q) use ($fromStart, $toEnd) {
                $q->where(function ($inner) use ($fromStart, $toEnd) {
                    $inner->whereNotNull('used_at')
                        ->whereDate('used_at', '>=', $fromStart->toDateString())
                        ->whereDate('used_at', '<=', $toEnd->toDateString());
                })->orWhere(function ($inner) use ($fromStart, $toEnd) {
                    $inner->whereNull('used_at')
                        ->whereBetween('created_at', [$fromStart, $toEnd]);
                });
            })
            ->orderByDesc('id')
            ->get();

        foreach ($ticketParts->groupBy('part_name') as $name => $group) {
            $qty = (int) $group->sum('quantity');
            $amount = (int) $group->sum('total_price');
            $existing = $rows->firstWhere('part_name', $name);
            if ($existing) {
                $existing->qty += $qty;
                $existing->amount += $amount;
                $existing->docs += $group->count();
            } else {
                $rows->push((object) [
                    'part_name' => $name,
                    'qty' => $qty,
                    'amount' => $amount,
                    'docs' => $group->count(),
                ]);
            }
        }
        $rows = $rows->sortByDesc('qty')->values();

        $stockTotals = [
            'lines' => $rows->count(),
            'qty' => (int) $rows->sum('qty'),
            'amount' => (int) $rows->sum('amount'),
            'docs' => $movements->count() + $ticketParts->count(),
        ];

        return view('reports.parts-used', [
            'exits' => $exits,
            'exitTotals' => $exitTotals,
            'payments' => $payments,
            'cashTotals' => $cashTotals,
            'rows' => $rows,
            'movements' => $movements,
            'ticketParts' => $ticketParts,
            'stockTotals' => $stockTotals,
            'from' => $from,
            'to' => $to,
            'period' => ReportSettings::get('period', 'custom'),
            'chartExitLabels' => jalali_day_labels($exitDaily->keys()->values()->all()),
            'chartExitValues' => $exitDaily->values()->values()->all(),
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
