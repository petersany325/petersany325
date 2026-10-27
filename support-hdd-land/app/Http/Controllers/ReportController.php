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
use App\Services\TechnicianPeriodSummary;
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
        $ticket = trim((string) $request->input('ticket_no', ''));
        $serial = trim((string) $request->input('serial', ''));
        $q = trim((string) $request->input('q', ''));

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
            $inHandQuery->where('ticket_no', 'like', '%'.$ticket.'%');
        }
        if ($serial !== '') {
            $inHandQuery->where('serial_number', 'like', '%'.$serial.'%');
        }
        if ($q !== '') {
            $inHandQuery->where(function ($inner) use ($q) {
                $inner->where('ticket_no', 'like', '%'.$q.'%')
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
            $query->whereHas('reception', fn ($r) => $r->where('ticket_no', 'like', '%'.$ticket.'%'));
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

        // Refund rows are stored as negative amounts; show absolute refund and true net.
        $totalIn = (int) (clone $payments)->where('type', '!=', 'refund')->sum('amount');
        $totalRefund = (int) abs((int) (clone $payments)->where('type', 'refund')->sum('amount'));
        $totalNet = $totalIn - $totalRefund;

        $byMethod = (clone $payments)
            ->select(
                'method',
                DB::raw("SUM(CASE WHEN type = 'refund' THEN 0 ELSE amount END) as amount"),
                DB::raw("SUM(CASE WHEN type = 'refund' THEN ABS(amount) ELSE 0 END) as refund_amount"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('method')
            ->orderByDesc('amount')
            ->get();

        $byType = (clone $payments)
            ->select('type', DB::raw('SUM(ABS(amount)) as amount'), DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('amount')
            ->get();

        $daily = (clone $payments)
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as net, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $gateway = GatewayTransaction::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(amount),0) as amount'))
            ->groupBy('status')
            ->get();

        $deliveredMoney = Reception::query()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->selectRaw('COALESCE(SUM(total_amount),0) as billed')
            ->selectRaw('COALESCE(SUM(paid_amount),0) as paid_on_ticket')
            ->selectRaw('COALESCE(SUM(labor_cost),0) as labor')
            ->selectRaw('COALESCE(SUM(parts_cost),0) as parts')
            ->first();

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

        $reconciliation = [
            'billed_delivered' => (int) ($deliveredMoney->billed ?? 0),
            'paid_on_delivered' => (int) ($deliveredMoney->paid_on_ticket ?? 0),
            'labor_delivered' => (int) ($deliveredMoney->labor ?? 0),
            'parts_delivered' => (int) ($deliveredMoney->parts ?? 0),
            'cash_collected' => $totalNet,
            'gap_billed_vs_cash' => (int) ($deliveredMoney->billed ?? 0) - $totalNet,
        ];

        $chartMethodLabels = $byMethod->map(fn ($r) => Payment::METHODS[$r->method] ?? $r->method)->values()->all();
        $chartMethodValues = $byMethod->pluck('amount')->map(fn ($v) => (int) $v)->values()->all();
        $chartDailyLabels = $daily->pluck('day')->values()->all();
        $chartDailyValues = $daily->pluck('net')->map(fn ($v) => (int) $v)->values()->all();

        return view('reports.payments', compact(
            'from', 'to', 'totalIn', 'totalRefund', 'totalNet', 'byMethod', 'byType',
            'daily', 'gateway', 'receivables', 'recent', 'reconciliation',
            'chartMethodLabels', 'chartMethodValues', 'chartDailyLabels', 'chartDailyValues'
        ));
    }

    public function sms(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $receptionId = $request->integer('reception_id') ?: null;
        $q = trim((string) $request->get('q'));
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
                DB::raw('COUNT(*) as cnt'),
                DB::raw('COALESCE(SUM(COALESCE(labor_cost,0) + COALESCE(parts_cost,0)),0) as total')
            )
            ->where('status', 'ready')
            ->whereNotNull('technician_id')
            ->groupBy('technician_id')
            ->get()
            ->keyBy('technician_id');

        $workshopMap = Reception::query()
            ->select(
                'technician_id',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('COALESCE(SUM(COALESCE(labor_cost,0) + COALESCE(parts_cost,0)),0) as total')
            )
            ->whereIn('status', ['received', 'repairing', 'waiting_part'])
            ->whereNotNull('technician_id')
            ->groupBy('technician_id')
            ->get()
            ->keyBy('technician_id');

        $unrepairableOpenMap = Reception::query()
            ->select('technician_id', DB::raw('COUNT(*) as cnt'))
            ->where('status', 'unrepairable')
            ->whereNotNull('technician_id')
            ->groupBy('technician_id')
            ->pluck('cnt', 'technician_id');

        $unrepairableExitedMap = Reception::query()
            ->select('technician_id', DB::raw('COUNT(*) as cnt'))
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->whereNotNull('technician_id')
            ->whereHas('statusLogs', function ($logs) {
                $logs->where('to_status', 'unrepairable');
            })
            ->groupBy('technician_id')
            ->pluck('cnt', 'technician_id');

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
            ->withSum([
                'receptions as billed_sum' => function ($q2) use ($from, $to) {
                    $q2->where('status', 'delivered')
                        ->whereDate('delivered_at', '>=', $from)
                        ->whereDate('delivered_at', '<=', $to);
                },
            ], 'total_amount')
            ->withSum([
                'receptions as paid_sum' => function ($q2) use ($from, $to) {
                    $q2->where('status', 'delivered')
                        ->whereDate('delivered_at', '>=', $from)
                        ->whereDate('delivered_at', '<=', $to);
                },
            ], 'paid_amount')
            ->orderBy('name')
            ->get()
            ->map(function (Technician $row) use ($inHandMap, $pendingExitMap, $workshopMap, $unrepairableOpenMap, $unrepairableExitedMap) {
                $labor = (int) ($row->labor_sum ?? 0);
                $pct = (float) ($row->commission_percent ?? 0);
                $row->labor_sum = $labor;
                $row->parts_sum = (int) ($row->parts_sum ?? 0);
                $row->billed_sum = (int) ($row->billed_sum ?? 0);
                $row->paid_sum = (int) ($row->paid_sum ?? 0);
                $row->commission_sum = (int) round($labor * $pct / 100);
                $row->in_hand_count = (int) ($inHandMap[$row->id] ?? 0);
                $pending = $pendingExitMap[$row->id] ?? null;
                $workshop = $workshopMap[$row->id] ?? null;
                $row->pending_exit_count = (int) ($pending->cnt ?? 0);
                $row->pending_exit_sum = (int) ($pending->total ?? 0);
                $row->workshop_count = (int) ($workshop->cnt ?? 0);
                $row->workshop_sum = (int) ($workshop->total ?? 0);
                $row->unrepairable_open = (int) ($unrepairableOpenMap[$row->id] ?? 0);
                $row->unrepairable_exited = (int) ($unrepairableExitedMap[$row->id] ?? 0);

                return $row;
            });

        $workSplit = app(TechnicianPeriodSummary::class)->forTechnicians($rows->pluck('id')->all(), $from, $to);
        $rows = $rows->map(function (Technician $row) use ($workSplit) {
            $split = $workSplit[(int) $row->id] ?? [];
            $row->work_count = (int) ($split['work_count'] ?? 0);
            $row->work_expense = (int) ($split['expense'] ?? 0);
            $row->exit_amount = (int) ($split['exit_amount'] ?? 0);
            $row->open_priced_count = (int) ($split['open_count'] ?? 0);
            $row->work_line = (string) ($split['line'] ?? '');

            return $row;
        });

        $totals = [
            'labor' => (int) $rows->sum('labor_sum'),
            'parts' => (int) $rows->sum('parts_sum'),
            'billed' => (int) $rows->sum('billed_sum'),
            'paid' => (int) $rows->sum('paid_sum'),
            'commission' => (int) $rows->sum('commission_sum'),
            'pending_exit' => (int) $rows->sum('pending_exit_sum'),
            'pending_exit_count' => (int) $rows->sum('pending_exit_count'),
            'workshop' => (int) $rows->sum('workshop_sum'),
            'workshop_count' => (int) $rows->sum('workshop_count'),
            'unrepairable_open' => (int) $rows->sum('unrepairable_open'),
            'unrepairable_exited' => (int) $rows->sum('unrepairable_exited'),
            'work_count' => (int) $rows->sum('work_count'),
            'work_expense' => (int) $rows->sum('work_expense'),
            'exit_amount' => (int) $rows->sum('exit_amount'),
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
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($inner) use ($from, $to) {
                    $inner->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
                })->orWhere(function ($inner) use ($from, $to) {
                    $inner->where('status', 'delivered')
                        ->whereDate('delivered_at', '>=', $from)
                        ->whereDate('delivered_at', '<=', $to);
                });
            })
            ->latest('id')
            ->get();

        $delivered = Reception::query()
            ->with(['customer', 'parts', 'payments'])
            ->where('technician_id', $technician->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->latest('delivered_at')
            ->get();

        $laborSum = (int) $delivered->sum('labor_cost');
        $partsSum = (int) $delivered->sum('parts_cost');
        $billedSum = (int) $delivered->sum('total_amount');
        $paidSum = (int) $delivered->sum('paid_amount');
        $commissionSum = (int) round($laborSum * ((float) $technician->commission_percent) / 100);

        $inHand = Reception::query()
            ->with('customer')
            ->where('custody', 'with_technician')
            ->where('custody_technician_id', $technician->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest('id')
            ->get();

        $pendingExit = Reception::query()
            ->with('customer')
            ->where('technician_id', $technician->id)
            ->where('status', 'ready')
            ->latest('id')
            ->get();

        $workshopOpen = Reception::query()
            ->with('customer')
            ->where('technician_id', $technician->id)
            ->whereIn('status', ['received', 'repairing', 'waiting_part'])
            ->latest('id')
            ->get();

        $unrepairableOpen = Reception::query()
            ->with('customer')
            ->where('technician_id', $technician->id)
            ->where('status', 'unrepairable')
            ->latest('id')
            ->get();

        $unrepairableExited = Reception::query()
            ->with('customer')
            ->where('technician_id', $technician->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->whereHas('statusLogs', fn ($logs) => $logs->where('to_status', 'unrepairable'))
            ->latest('delivered_at')
            ->get();

        $partsUsed = ReceptionPart::query()
            ->select('part_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total_price) as amount'))
            ->whereHas('reception', fn ($q) => $q->where('technician_id', $technician->id))
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($inner) use ($from, $to) {
                    $inner->whereDate('used_at', '>=', $from)->whereDate('used_at', '<=', $to);
                })->orWhere(function ($inner) use ($from, $to) {
                    $inner->whereNull('used_at')
                        ->whereHas('reception', function ($r) use ($from, $to) {
                            $r->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
                        });
                });
            })
            ->groupBy('part_name')
            ->orderByDesc('qty')
            ->get();

        $partsUsedTotal = (int) $partsUsed->sum('amount');

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

        $daily = $delivered->groupBy(fn ($r) => optional($r->delivered_at)->toDateString())
            ->map->count()
            ->sortKeys();

        $avgDays = $delivered
            ->filter(fn ($r) => $r->received_at && $r->delivered_at)
            ->avg(fn ($r) => $r->received_at->diffInHours($r->delivered_at) / 24);

        $periodSummary = app(TechnicianPeriodSummary::class);
        $workSplit = $periodSummary->forTechnicians([$technician->id], $from, $to)[(int) $technician->id] ?? [];
        $pricedOpen = $periodSummary->openReceipts((int) $technician->id, $from, $to);
        $workLine = (string) ($workSplit['line'] ?? '');
        $workCount = (int) ($workSplit['work_count'] ?? 0);
        $workExpense = (int) ($workSplit['expense'] ?? 0);
        $exitAmount = (int) ($workSplit['exit_amount'] ?? 0);
        $exitParts = (int) ($workSplit['exit_parts'] ?? 0);

        $breakdown = [
            'parts_expense' => $partsUsedTotal,
            'repaired_billed' => $billedSum,
            'repaired_labor' => $laborSum,
            'repaired_parts' => $partsSum,
            'repaired_paid' => $paidSum,
            'pending_exit_count' => $pendingExit->count(),
            'pending_exit_sum' => (int) $pendingExit->sum(fn ($r) => (int) $r->labor_cost + (int) $r->parts_cost),
            'workshop_count' => $workshopOpen->count(),
            'workshop_sum' => (int) $workshopOpen->sum(fn ($r) => (int) $r->labor_cost + (int) $r->parts_cost),
            'unrepairable_open' => $unrepairableOpen->count(),
            'unrepairable_exited' => $unrepairableExited->count(),
            'work_count' => $workCount,
            'work_expense' => $workExpense,
            'exit_amount' => $exitAmount,
            'exit_parts' => $exitParts,
        ];

        return view('reports.technician-show', compact(
            'technician', 'from', 'to', 'jobs', 'delivered', 'laborSum', 'partsSum',
            'billedSum', 'paidSum', 'commissionSum', 'inHand', 'partsUsed', 'partsUsedTotal',
            'pendingExit', 'workshopOpen', 'unrepairableOpen', 'unrepairableExited',
            'pricedOpen', 'workLine', 'workCount', 'workExpense', 'exitAmount', 'exitParts',
            'handoffs', 'byStatus', 'statusLabels', 'breakdown',
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
