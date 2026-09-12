<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallmentItem;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use Illuminate\Http\Request;

class InstallmentReportController extends Controller
{
    public function index(Request $request)
    {
        $customerId = (int) $request->query('customer_id', 0);
        $filter = (string) $request->query('filter', 'unpaid'); // unpaid|paid|all|overdue
        $from = resolve_request_date($request->query('from'));
        $to = resolve_request_date($request->query('to'));

        $items = InstallmentItem::query()
            ->with(['plan.customer'])
            ->when($customerId > 0, fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('customer_id', $customerId)))
            ->when($filter === 'unpaid', fn ($q) => $q->whereIn('status', ['pending', 'partial', 'overdue'])->whereColumn('paid_amount', '<', 'amount'))
            ->when($filter === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->when($filter === 'overdue', fn ($q) => $q->where(function ($w) {
                $w->where('status', 'overdue')
                    ->orWhere(function ($x) {
                        $x->whereIn('status', ['pending', 'partial'])
                            ->whereColumn('paid_amount', '<', 'amount')
                            ->whereDate('due_date', '<', now()->toDateString());
                    });
            }))
            ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to))
            ->whereHas('plan', fn ($p) => $p->where('status', '!=', 'cancelled'))
            ->orderBy('due_date')
            ->paginate(40)
            ->withQueryString();

        $sumScheduled = (clone $items->getCollection())->sum('amount');
        // Better aggregate without pagination constraint:
        $aggQuery = InstallmentItem::query()
            ->when($customerId > 0, fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('customer_id', $customerId)))
            ->when($filter === 'unpaid', fn ($q) => $q->whereIn('status', ['pending', 'partial', 'overdue'])->whereColumn('paid_amount', '<', 'amount'))
            ->when($filter === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->when($filter === 'overdue', fn ($q) => $q->where(function ($w) {
                $w->where('status', 'overdue')
                    ->orWhere(function ($x) {
                        $x->whereIn('status', ['pending', 'partial'])
                            ->whereColumn('paid_amount', '<', 'amount')
                            ->whereDate('due_date', '<', now()->toDateString());
                    });
            }))
            ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to))
            ->whereHas('plan', fn ($p) => $p->where('status', '!=', 'cancelled'));

        $totals = [
            'scheduled' => (int) (clone $aggQuery)->sum('amount'),
            'paid' => (int) (clone $aggQuery)->sum('paid_amount'),
        ];
        $totals['remain'] = max(0, $totals['scheduled'] - $totals['paid']);

        $payments = InstallmentPayment::query()
            ->with(['plan.customer', 'item'])
            ->when($customerId > 0, fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('customer_id', $customerId)))
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to))
            ->latest('paid_at')
            ->limit(50)
            ->get();

        return view('installments.report', [
            'items' => $items,
            'payments' => $payments,
            'totals' => $totals,
            'filter' => $filter,
            'customerId' => $customerId,
            'customer' => $customerId > 0 ? Customer::find($customerId) : null,
            'from' => $from,
            'to' => $to,
            'sumScheduled' => $sumScheduled,
        ]);
    }
}
