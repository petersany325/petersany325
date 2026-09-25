<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccCommerce;
use Plugins\Accounting\src\Support\AccEngine;
use Plugins\Accounting\src\Support\AccSafe;

class ReportController extends Controller
{
    public function __construct()
    {
        try {
            \Plugins\Accounting\src\Support\AccRoutes::registerViews();
            Plugin::ensureSchema();
        } catch (\Throwable) {
        }
    }

    public function hub(Request $request)
    {
        try {
            return $this->hubInner($request);
        } catch (\Throwable) {
            return AccSafe::page('accounting::admin.reports.hub', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
                'sales' => 0,
                'purchase' => 0,
                'expense' => 0,
                'commission' => 0,
                'profit' => 0,
                'quick' => [],
                'links' => [],
            ], 'مرکز گزارش‌ها');
        }
    }

    protected function hubInner(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $sum = function (string $type) use ($from, $to): int {
            try {
                if (! Schema::hasTable('acc_documents')) {
                    return 0;
                }
                $q = DB::table('acc_documents')->where('type', $type)->where('status', 'issued')->whereBetween('doc_date', [$from, $to]);

                return (int) $q->sum('total');
            } catch (\Throwable) {
                return 0;
            }
        };
        $sales = $sum('sale');
        $purchase = $sum('purchase');
        $expense = $sum('expense');
        $commission = 0;
        try {
            if (Schema::hasTable('acc_documents') && Schema::hasColumn('acc_documents', 'commission_amount')) {
                $commission = (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('commission_amount');
            }
        } catch (\Throwable) {
        }

        return AccSafe::page('accounting::admin.reports.hub', [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'purchase' => $purchase,
            'expense' => $expense,
            'commission' => $commission,
            'profit' => $sales - $purchase - $expense - $commission,
            'quick' => [
                'sales_today' => (int) $this->safeSum('sale', $today, $today),
                'sales_month' => (int) $this->safeSum('sale', $monthStart, $today),
                'purchases_month' => (int) $this->safeSum('purchase', $monthStart, $today),
                'checks_open' => (int) (function () {
                    try {
                        return Schema::hasTable('acc_checks') ? DB::table('acc_checks')->whereIn('status', ['pending', 'received', 'delivered', 'in_collection'])->count() : 0;
                    } catch (\Throwable) {
                        return 0;
                    }
                })(),
                'installments_pending' => (int) (function () {
                    try {
                        return Schema::hasTable('acc_installment_requests') ? DB::table('acc_installment_requests')->where('status', 'pending')->count() : 0;
                    } catch (\Throwable) {
                        return 0;
                    }
                })(),
                'warehouses' => (int) (function () {
                    try {
                        return Schema::hasTable('acc_warehouses') ? DB::table('acc_warehouses')->where('is_active', true)->count() : 0;
                    } catch (\Throwable) {
                        return 0;
                    }
                })(),
                'check_alerts' => (int) (function () {
                    try {
                        return count(AccEngine::dueCheckAlerts());
                    } catch (\Throwable) {
                        return 0;
                    }
                })(),
            ],
            'links' => [
                ['href' => '/admin/accounting/reports/sales', 'label' => 'فروش و خرید', 'desc' => 'فیلتر تاریخ، شماره فاکتور، طرف حساب، انبار، فروشنده'],
                ['href' => '/admin/accounting/reports/staff', 'label' => 'کارمندان و فروشندگان', 'desc' => 'فروش و سود/کمیسیون هر کارمند'],
                ['href' => '/admin/accounting/reports/payroll', 'label' => 'حقوق و مزایا', 'desc' => 'فیش‌ها و خالص پرداختی'],
                ['href' => '/admin/accounting/reports/vouchers', 'label' => 'اسناد حسابداری', 'desc' => 'سند دستی و هزینه با شماره سند'],
                ['href' => '/admin/accounting/reports/warehouse', 'label' => 'انبارها با تفکیک', 'desc' => 'موجودی و ارزش هر انبار'],
                ['href' => '/admin/accounting/reports/customers', 'label' => 'مشتریان', 'desc' => 'جمع خرید هر مشتری'],
                ['href' => '/admin/accounting/reports/checks', 'label' => 'چک‌ها', 'desc' => 'پرداختی، دریافتی، برگشتی، تحویل'],
                ['href' => '/admin/accounting/reports/installments', 'label' => 'اقساط', 'desc' => 'درخواست‌های اقساطی مشتریان'],
                ['href' => '/admin/accounting/reports/shop-stock', 'label' => 'موجودی فروشگاه', 'desc' => 'تطبیق انبار حسابداری با موجودی سایت'],
                ['href' => '/admin/accounting/reports/trial', 'label' => 'تراز آزمایشی', 'desc' => 'مانده هر حساب از دفتر کل'],
                ['href' => '/admin/accounting/reports/income', 'label' => 'سود و زیان', 'desc' => 'درآمد، بها و هزینه از روی کدینگ'],
                ['href' => '/admin/accounting/reports/balance', 'label' => 'ترازنامه', 'desc' => 'دارایی، بدهی و سرمایه'],
            ],
        ]);
    }

    public function shopStock()
    {
        return AccSafe::wrap('accounting::admin.reports.shop-stock', function () {
            $rows = [];
            if (Schema::hasTable('products')) {
                $products = AccCommerce::catalogProducts('', 400);
                $acc = [];
                if (Schema::hasTable('acc_stock_balances')) {
                    foreach (DB::table('acc_stock_balances')->select('product_id', DB::raw('SUM(qty) as qty'))->groupBy('product_id')->get() as $b) {
                        $acc[(int) $b->product_id] = (float) $b->qty;
                    }
                }
                $sold = [];
                if (Schema::hasTable('acc_document_lines') && Schema::hasTable('acc_documents')) {
                    foreach (DB::table('acc_document_lines as l')
                        ->join('acc_documents as d', 'd.id', '=', 'l.document_id')
                        ->where('d.type', 'sale')->where('d.status', 'issued')
                        ->whereNotNull('l.product_id')
                        ->select('l.product_id', DB::raw('SUM(l.qty) as qty'))
                        ->groupBy('l.product_id')->get() as $s) {
                        $sold[(int) $s->product_id] = (float) $s->qty;
                    }
                }
                foreach ($products as $p) {
                    $site = (float) ($p->stock ?? 0);
                    $wh = (float) ($acc[(int) $p->id] ?? 0);
                    $rows[] = (object) [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku ?? '',
                        'site' => $site,
                        'warehouse' => $wh,
                        'sold' => (float) ($sold[(int) $p->id] ?? 0),
                        'diff' => $site - $wh,
                    ];
                }
            }

            return ['rows' => $rows];
        }, 'موجودی فروشگاه');
    }

    public function sales(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.sales', function () use ($request) {
            [$from, $to] = $this->range($request);
            $type = in_array($request->get('type'), ['sale', 'purchase'], true) ? $request->get('type') : 'sale';
            $docNo = trim((string) $request->get('doc_no', ''));
            $party = trim((string) $request->get('party', ''));
            $warehouseId = (int) $request->get('warehouse_id', 0);
            $staffId = (int) $request->get('staff_id', 0);
            $empty = [
                'type' => $type, 'from' => $from, 'to' => $to,
                'filters' => compact('docNo', 'party', 'warehouseId', 'staffId'),
                'rows' => collect(),
                'sum' => ['count' => 0, 'subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0, 'commission' => 0],
                'warehouses' => collect(), 'staff' => $this->staffOptions(), 'types' => AccEngine::TYPES,
            ];
            if (! Schema::hasTable('acc_documents')) {
                return $empty;
            }
            $select = [
                'd.id', 'd.number', 'd.doc_date', 'd.party_name', 'd.party_user_id',
                'd.subtotal', 'd.discount', 'd.tax', 'd.total',
                'd.staff_id',
                'w.name as warehouse_name',
            ];
            if (Schema::hasColumn('acc_documents', 'commission_amount')) {
                $select[] = 'd.commission_amount';
            }
            $q = DB::table('acc_documents as d')
                ->leftJoin('acc_warehouses as w', 'w.id', '=', 'd.warehouse_id')
                ->where('d.type', $type)
                ->where('d.status', 'issued')
                ->whereBetween('d.doc_date', [$from, $to]);
            if (Schema::hasTable('staff_members')) {
                $q->leftJoin('staff_members as sm', 'sm.id', '=', 'd.staff_id');
                $select[] = DB::raw('sm.name as staff_name');
            } else {
                $select[] = DB::raw("'' as staff_name");
            }
            $q->select($select);

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
            $warehouses = Schema::hasTable('acc_warehouses')
                ? DB::table('acc_warehouses')->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect();

            return [
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
                'warehouses' => $warehouses,
                'staff' => $this->staffOptions(),
                'types' => AccEngine::TYPES,
            ];
        }, 'گزارش فروش و خرید');
    }

    public function staff(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.staff', function () use ($request) {
            [$from, $to] = $this->range($request);
            $staffId = (int) $request->get('staff_id', 0);
            $rows = collect();
            if (Schema::hasTable('acc_documents')) {
                $q = DB::table('acc_documents as d')
                    ->where('d.type', 'sale')
                    ->where('d.status', 'issued')
                    ->whereBetween('d.doc_date', [$from, $to])
                    ->whereNotNull('d.staff_id');
                $nameExpr = 'CONCAT("#", d.staff_id)';
                if (Schema::hasTable('staff_members')) {
                    $q->leftJoin('staff_members as sm', 'sm.id', '=', 'd.staff_id');
                    $nameExpr = 'COALESCE(MAX(sm.name), CONCAT("#", d.staff_id))';
                }
                if ($staffId > 0) {
                    $q->where('d.staff_id', $staffId);
                }
                $comm = Schema::hasColumn('acc_documents', 'commission_amount') ? 'SUM(d.commission_amount)' : '0';
                $rows = $q->selectRaw('d.staff_id, '.$nameExpr.' as staff_name, COUNT(*) as docs_count, SUM(d.total) as sales_total, '.$comm.' as commission_total, SUM(d.total) - '.$comm.' as profit_est')
                    ->groupBy('d.staff_id')
                    ->orderByDesc('sales_total')
                    ->get();
            }

            return [
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
            ];
        }, 'گزارش کارمندان');
    }

    public function payroll(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.payroll', function () use ($request) {
            [$from, $to] = $this->range($request, 'month');
            $staffId = (int) $request->get('staff_id', 0);
            $rows = collect();
            if (Schema::hasTable('acc_payslips') && Schema::hasTable('acc_payroll_runs')) {
                $q = DB::table('acc_payslips as p')
                    ->join('acc_payroll_runs as r', 'r.id', '=', 'p.payroll_run_id')
                    ->whereBetween('r.created_at', [$from.' 00:00:00', $to.' 23:59:59']);
                if ($staffId > 0) {
                    $q->where('p.staff_id', $staffId);
                }
                $rows = $q->select(['p.*', 'r.period', 'r.status as run_status'])
                    ->orderByDesc('r.id')->limit(500)->get();
            }

            return [
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
            ];
        }, 'گزارش حقوق');
    }

    public function vouchers(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.vouchers', function () use ($request) {
            [$from, $to] = $this->range($request);
            $docNo = trim((string) $request->get('doc_no', ''));
            $type = in_array($request->get('type'), ['voucher', 'expense', ''], true) ? (string) $request->get('type') : '';
            $rows = collect();
            if (Schema::hasTable('acc_documents')) {
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
            }

            return [
                'from' => $from,
                'to' => $to,
                'type' => $type,
                'docNo' => $docNo,
                'rows' => $rows,
                'sum' => ['total' => (int) $rows->sum('total'), 'count' => $rows->count()],
                'types' => AccEngine::TYPES,
            ];
        }, 'گزارش اسناد');
    }

    public function warehouse(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.warehouse', function () use ($request) {
            $warehouseId = (int) $request->get('warehouse_id', 0);
            $rows = collect();
            $byWh = collect();
            $warehouses = collect();
            if (Schema::hasTable('acc_warehouses')) {
                $warehouses = DB::table('acc_warehouses')->orderByDesc('is_default')->orderBy('name')->get();
            }
            if (Schema::hasTable('acc_stock_balances')) {
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
            }

            return [
                'warehouseId' => $warehouseId,
                'warehouses' => $warehouses,
                'rows' => $rows,
                'byWh' => $byWh,
            ];
        }, 'گزارش انبار');
    }

    public function customers(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.customers', function () use ($request) {
            [$from, $to] = $this->range($request);
            $party = trim((string) $request->get('party', ''));
            $rows = collect();
            if (Schema::hasTable('acc_documents')) {
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
            }

            return [
                'from' => $from,
                'to' => $to,
                'party' => $party,
                'rows' => $rows,
                'sum' => [
                    'docs' => (int) $rows->sum('docs_count'),
                    'sales' => (int) $rows->sum('sales_total'),
                    'discount' => (int) $rows->sum('discount_total'),
                ],
            ];
        }, 'گزارش مشتریان');
    }

    public function checks(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.checks', function () use ($request) {
            [$from, $to] = $this->range($request);
            $status = trim((string) $request->get('status', ''));
            $direction = trim((string) $request->get('direction', ''));
            $q = trim((string) $request->get('q', ''));
            $rows = collect();
            $byStatus = collect();
            if (Schema::hasTable('acc_checks')) {
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
                if (in_array($direction, ['receivable', 'payable', 'spent'], true)) {
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
            }

            return [
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
            ];
        }, 'گزارش چک‌ها');
    }

    public function installments(Request $request)
    {
        return AccSafe::wrap('accounting::admin.reports.installments', function () use ($request) {
            $status = trim((string) $request->get('status', ''));
            $rows = collect();
            if (Schema::hasTable('acc_installment_requests')) {
                $q = DB::table('acc_installment_requests as r')
                    ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
                    ->select(array_merge(['r.*'], AccEngine::userAliasColumns('u')));
                if ($status !== '') {
                    $q->where('r.status', $status);
                }
                $rows = $q->orderByDesc('r.id')->limit(300)->get();
            }

            return [
                'status' => $status,
                'rows' => $rows,
                'statuses' => AccEngine::INSTALLMENT_STATUSES,
            ];
        }, 'گزارش اقساط');
    }

    private function safeSum(string $type, string $from, string $to): int
    {
        try {
            if (! Schema::hasTable('acc_documents')) {
                return 0;
            }

            return (int) DB::table('acc_documents')->where('type', $type)->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        } catch (\Throwable) {
            return 0;
        }
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
        try {
            if (Schema::hasTable('staff_members')) {
                return DB::table('staff_members')->where('is_active', 1)->orderBy('name')->get(['id', 'name', 'role']);
            }
            if (! Schema::hasTable('users')) {
                return collect();
            }
            $users = DB::table('users')->orderBy('name');
            $cols = ['id', 'name'];
            if (Schema::hasColumn('users', 'role')) {
                $users->whereIn('role', ['admin', 'staff', 'seller', 'warehouse']);
                $cols[] = 'role';
            }

            return $users->get($cols);
        } catch (\Throwable) {
            return collect();
        }
    }
}
