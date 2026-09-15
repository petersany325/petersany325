<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;

class ReportController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
    }

    public function hub(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $sales = (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $purchase = (int) DB::table('acc_documents')->where('type', 'purchase')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $expense = (int) DB::table('acc_documents')->where('type', 'expense')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $commission = (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('commission_amount');

        return view('accounting::admin.reports.hub', [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'purchase' => $purchase,
            'expense' => $expense,
            'commission' => $commission,
            'profit' => $sales - $purchase - $expense - $commission,
            'quick' => [
                'sales_today' => (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereDate('doc_date', $today)->sum('total'),
                'sales_month' => (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$monthStart, $today])->sum('total'),
                'purchases_month' => (int) DB::table('acc_documents')->where('type', 'purchase')->where('status', 'issued')->whereBetween('doc_date', [$monthStart, $today])->sum('total'),
                'checks_open' => Schema::hasTable('acc_checks') ? (int) DB::table('acc_checks')->whereIn('status', ['pending', 'received', 'delivered'])->count() : 0,
                'installments_pending' => Schema::hasTable('acc_installment_requests') ? (int) DB::table('acc_installment_requests')->where('status', 'pending')->count() : 0,
                'warehouses' => (int) DB::table('acc_warehouses')->where('is_active', true)->count(),
            ],
            'links' => [
                ['route' => 'admin.accounting.reports.sales', 'label' => 'فروش و خرید', 'desc' => 'فیلتر تاریخ، شماره فاکتور، طرف حساب، انبار، فروشنده'],
                ['route' => 'admin.accounting.reports.staff', 'label' => 'کارمندان و فروشندگان', 'desc' => 'فروش و سود/کمیسیون هر کارمند'],
                ['route' => 'admin.accounting.reports.payroll', 'label' => 'حقوق و مزایا', 'desc' => 'فیش‌ها و خالص پرداختی'],
                ['route' => 'admin.accounting.reports.vouchers', 'label' => 'اسناد حسابداری', 'desc' => 'سند دستی و هزینه با شماره سند'],
                ['route' => 'admin.accounting.reports.warehouse', 'label' => 'انبارها با تفکیک', 'desc' => 'موجودی و ارزش هر انبار'],
                ['route' => 'admin.accounting.reports.customers', 'label' => 'مشتریان', 'desc' => 'جمع خرید هر مشتری'],
                ['route' => 'admin.accounting.reports.checks', 'label' => 'چک‌ها', 'desc' => 'پرداختی، دریافتی، برگشتی، تحویل'],
                ['route' => 'admin.accounting.reports.installments', 'label' => 'اقساط', 'desc' => 'درخواست‌های اقساطی مشتریان'],
            ],
        ]);
    }

    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);
        $type = in_array($request->get('type'), ['sale', 'purchase'], true) ? $request->get('type') : 'sale';
        $docNo = trim((string) $request->get('doc_no', ''));
        $party = trim((string) $request->get('party', ''));
        $warehouseId = (int) $request->get('warehouse_id', 0);
        $staffId = (int) $request->get('staff_id', 0);

        $q = DB::table('acc_documents as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.staff_id')
            ->leftJoin('acc_warehouses as w', 'w.id', '=', 'd.warehouse_id')
            ->where('d.type', $type)
            ->where('d.status', 'issued')
            ->whereBetween('d.doc_date', [$from, $to])
            ->select([
                'd.id', 'd.number', 'd.doc_date', 'd.party_name', 'd.party_user_id',
                'd.subtotal', 'd.discount', 'd.tax', 'd.total',
                'd.commission_amount', 'd.staff_id', 'u.name as staff_name', 'w.name as warehouse_name',
            ]);

        if ($docNo !== '') {
            $q->where('d.number', 'like', '%'.$docNo.'%');
        }
        if ($party !== '') {
            $q->where(function ($w) use ($party) {
                $w->where('d.party_name', 'like', '%'.$party.'%')->orWhere('d.party_user_id', $party);
            });
        }
        if ($warehouseId > 0) {
            $q->where('d.warehouse_id', $warehouseId);
        }
        if ($staffId > 0) {
            $q->where('d.staff_id', $staffId);
        }

        $rows = $q->orderByDesc('d.doc_date')->orderByDesc('d.id')->limit(500)->get();

        return view('accounting::admin.reports.sales', [
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'filters' => compact('docNo', 'party', 'warehouseId', 'staffId'),
            'rows' => $rows,
            'sum' => [
                'count' => $rows->count(),
                'subtotal' => (int) $rows->sum('subtotal'),
                'discount' => (int) $rows->sum('discount'),
                'tax' => (int) $rows->sum('tax'),
                'total' => (int) $rows->sum('total'),
                'commission' => (int) $rows->sum('commission_amount'),
            ],
            'warehouses' => DB::table('acc_warehouses')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'staff' => $this->staffOptions(),
            'types' => AccEngine::TYPES,
        ]);
    }

    public function staff(Request $request)
    {
        [$from, $to] = $this->range($request);
        $staffId = (int) $request->get('staff_id', 0);

        $q = DB::table('acc_documents as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.staff_id')
            ->where('d.type', 'sale')
            ->where('d.status', 'issued')
            ->whereBetween('d.doc_date', [$from, $to])
            ->whereNotNull('d.staff_id');
        if ($staffId > 0) {
            $q->where('d.staff_id', $staffId);
        }

        $rows = $q->selectRaw('d.staff_id, COALESCE(u.name, CONCAT("#", d.staff_id)) as staff_name, COUNT(*) as docs_count, SUM(d.total) as sales_total, SUM(d.commission_amount) as commission_total, SUM(d.total - d.commission_amount) as profit_est')
            ->groupBy('d.staff_id', 'u.name')
            ->orderByDesc('sales_total')
            ->get();

        return view('accounting::admin.reports.staff', [
            'from' => $from,
            'to' => $to,
            'staffId' => $staffId,
            'rows' => $rows,
            'staff' => $this->staffOptions(),
            'sum' => [
                'docs' => (int) $rows->sum('docs_count'),
                'sales' => (int) $rows->sum('sales_total'),
                'commission' => (int) $rows->sum('commission_total'),
                'profit' => (int) $rows->sum('profit_est'),
            ],
        ]);
    }

    public function payroll(Request $request)
    {
        [$from, $to] = $this->range($request, 'month');
        $staffId = (int) $request->get('staff_id', 0);

        $q = DB::table('acc_payslips as p')
            ->join('acc_payroll_runs as r', 'r.id', '=', 'p.payroll_run_id')
            ->whereBetween('r.created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if ($staffId > 0) {
            $q->where('p.staff_id', $staffId);
        }

        $rows = $q->select(['p.*', 'r.period', 'r.status as run_status'])
            ->orderByDesc('r.id')->limit(500)->get();

        return view('accounting::admin.reports.payroll', [
            'from' => $from,
            'to' => $to,
            'staffId' => $staffId,
            'rows' => $rows,
            'staff' => $this->staffOptions(),
            'sum' => [
                'base' => (int) $rows->sum('base_salary'),
                'commission' => (int) $rows->sum('commission_amount'),
                'deduction' => (int) $rows->sum('deduction'),
                'net' => (int) $rows->sum('net'),
            ],
        ]);
    }

    public function vouchers(Request $request)
    {
        [$from, $to] = $this->range($request);
        $docNo = trim((string) $request->get('doc_no', ''));
        $type = in_array($request->get('type'), ['voucher', 'expense', ''], true) ? (string) $request->get('type') : '';

        $q = DB::table('acc_documents as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.created_by')
            ->whereIn('d.type', $type !== '' ? [$type] : ['voucher', 'expense'])
            ->where('d.status', 'issued')
            ->whereBetween('d.doc_date', [$from, $to])
            ->select(['d.*', 'u.name as staff_name']);
        if ($docNo !== '') {
            $q->where('d.number', 'like', '%'.$docNo.'%');
        }

        $rows = $q->orderByDesc('d.doc_date')->limit(500)->get();

        return view('accounting::admin.reports.vouchers', [
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'docNo' => $docNo,
            'rows' => $rows,
            'sum' => ['total' => (int) $rows->sum('total'), 'count' => $rows->count()],
            'types' => AccEngine::TYPES,
        ]);
    }

    public function warehouse(Request $request)
    {
        $warehouseId = (int) $request->get('warehouse_id', 0);
        $q = DB::table('acc_stock_balances as s')
            ->leftJoin('acc_warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->select(['s.*', 'w.name as warehouse_name', 'w.code as warehouse_code']);

        if (Schema::hasTable('products')) {
            $q->leftJoin('products as p', 'p.id', '=', 's.product_id')
                ->addSelect(DB::raw('COALESCE(p.name, CONCAT("کالا #", s.product_id)) as product_name'))
                ->addSelect(DB::raw('COALESCE(p.sku, "") as sku'));
        } else {
            $q->addSelect(DB::raw('CONCAT("کالا #", s.product_id) as product_name'))
                ->addSelect(DB::raw('"" as sku'));
        }

        if ($warehouseId > 0) {
            $q->where('s.warehouse_id', $warehouseId);
        }
        $rows = $q->orderBy('w.name')->orderBy('s.product_id')->limit(2000)->get();

        $byWh = DB::table('acc_stock_balances as s')
            ->join('acc_warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->selectRaw('w.id, w.name, w.code, COUNT(*) as skus, SUM(s.qty) as qty_sum, SUM(s.qty * s.avg_cost) as value_sum')
            ->groupBy('w.id', 'w.name', 'w.code')
            ->orderBy('w.name')
            ->get();

        return view('accounting::admin.reports.warehouse', [
            'warehouseId' => $warehouseId,
            'warehouses' => DB::table('acc_warehouses')->orderByDesc('is_default')->orderBy('name')->get(),
            'rows' => $rows,
            'byWh' => $byWh,
        ]);
    }

    public function customers(Request $request)
    {
        [$from, $to] = $this->range($request);
        $party = trim((string) $request->get('party', ''));

        $q = DB::table('acc_documents')
            ->where('type', 'sale')
            ->where('status', 'issued')
            ->whereBetween('doc_date', [$from, $to]);
        if ($party !== '') {
            $q->where(function ($w) use ($party) {
                $w->where('party_name', 'like', '%'.$party.'%')->orWhere('party_user_id', $party);
            });
        }

        $rows = $q->selectRaw('COALESCE(party_user_id, 0) as party_user_id, COALESCE(NULLIF(party_name, ""), CONCAT("کاربر #", COALESCE(party_user_id,0))) as party_label, COUNT(*) as docs_count, SUM(total) as sales_total, SUM(discount) as discount_total')
            ->groupBy('party_user_id', 'party_name')
            ->orderByDesc('sales_total')
            ->limit(500)
            ->get();

        return view('accounting::admin.reports.customers', [
            'from' => $from,
            'to' => $to,
            'party' => $party,
            'rows' => $rows,
            'sum' => [
                'docs' => (int) $rows->sum('docs_count'),
                'sales' => (int) $rows->sum('sales_total'),
                'discount' => (int) $rows->sum('discount_total'),
            ],
        ]);
    }

    public function checks(Request $request)
    {
        if (! Schema::hasTable('acc_checks')) {
            return redirect()->route('admin.accounting.reports')->with('error', 'جدول چک‌ها آماده نیست.');
        }

        [$from, $to] = $this->range($request);
        $status = trim((string) $request->get('status', ''));
        $direction = trim((string) $request->get('direction', ''));
        $q = trim((string) $request->get('q', ''));

        $query = DB::table('acc_checks as c')
            ->leftJoin('acc_banks as b', 'b.id', '=', 'c.bank_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.party_user_id')
            ->where(function ($w) use ($from, $to) {
                $w->whereBetween('c.due_date', [$from, $to])
                    ->orWhere(function ($x) use ($from, $to) {
                        $x->whereNull('c.due_date')->whereBetween('c.issue_date', [$from, $to]);
                    });
            })
            ->select(['c.*', 'b.name as linked_bank', 'u.name as party_user_name']);

        if ($status !== '') {
            $query->where('c.status', $status);
        }
        if (in_array($direction, ['receivable', 'payable'], true)) {
            $query->where('c.direction', $direction);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('c.number', 'like', '%'.$q.'%')
                    ->orWhere('c.sayad', 'like', '%'.$q.'%')
                    ->orWhere('c.party_name', 'like', '%'.$q.'%')
                    ->orWhere('c.bank_name', 'like', '%'.$q.'%');
            });
        }

        $rows = $query->orderBy('c.due_date')->limit(500)->get();
        $byStatus = DB::table('acc_checks')
            ->selectRaw('status, direction, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('status', 'direction')
            ->get();

        return view('accounting::admin.reports.checks', [
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'direction' => $direction,
            'q' => $q,
            'rows' => $rows,
            'byStatus' => $byStatus,
            'sum' => ['count' => $rows->count(), 'amount' => (int) $rows->sum('amount')],
            'statuses' => AccEngine::CHECK_STATUSES,
            'directions' => AccEngine::CHECK_DIRECTIONS,
        ]);
    }

    public function installments(Request $request)
    {
        if (! Schema::hasTable('acc_installment_requests')) {
            return redirect()->route('admin.accounting.reports')->with('error', 'جدول اقساط آماده نیست.');
        }

        $status = trim((string) $request->get('status', ''));
        $q = DB::table('acc_installment_requests as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->select(['r.*', 'u.name as user_name', 'u.email as user_email']);
        if ($status !== '') {
            $q->where('r.status', $status);
        }

        return view('accounting::admin.reports.installments', [
            'status' => $status,
            'rows' => $q->orderByDesc('r.id')->limit(300)->get(),
            'statuses' => AccEngine::INSTALLMENT_STATUSES,
        ]);
    }

    private function range(Request $request, string $default = 'month'): array
    {
        $to = $request->get('to') ?: now()->toDateString();
        $from = $request->filled('from')
            ? (string) $request->get('from')
            : ($default === 'month' ? now()->startOfMonth()->toDateString() : now()->subDays(30)->toDateString());

        return [$from, $to];
    }

    private function staffOptions()
    {
        return DB::table('users')
            ->whereIn('role', ['admin', 'staff', 'seller', 'warehouse'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}
