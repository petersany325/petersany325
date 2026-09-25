<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccCommerce;
use Plugins\Accounting\src\Support\AccEngine;

class HubController extends Controller
{
    public function __construct()
    {
        try {
            \Plugins\Accounting\src\Support\AccRoutes::registerViews();
            Plugin::loadClasses();
            Plugin::ensureSchema();
            Plugin::seedDefaults();
        } catch (\Throwable) {
        }
    }

    public function hub()
    {
        try {
            \Plugins\Accounting\src\Support\AccRoutes::registerViews();
        } catch (\Throwable) {
        }
        $synced = 0;
        try {
            $synced = AccCommerce::syncPendingShopOrders(25);
        } catch (\Throwable) {
        }
        $recent = collect();
        try {
            if (Schema::hasTable('acc_documents')) {
                $recent = DB::table('acc_documents')->orderByDesc('id')->limit(12)->get();
            }
        } catch (\Throwable) {
        }

        $stats = [
            'sales_total' => 0, 'purchase_total' => 0, 'expense_total' => 0, 'proforma_open' => 0,
            'warehouses' => 0, 'banks' => 0, 'docs' => [], 'check_alerts' => 0,
        ];
        try {
            $stats = AccEngine::dashboardStats() + $stats;
        } catch (\Throwable) {
        }

        try {
            return view('accounting::admin.hub', [
                'stats' => $stats,
                'types' => AccEngine::TYPES,
                'recent' => $recent,
                'synced' => $synced,
            ]);
        } catch (\Throwable) {
            return response($this->safeHubHtml(), 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }

    protected function safeHubHtml(): string
    {
        $links = [
            ['میز کار', '/admin/accounting'],
            ['کارمند و ویزیتور', '/admin/accounting/staff'],
            ['حقوق و دستمزد', '/admin/accounting/payroll'],
            ['تعریف کالا', '/admin/accounting/goods'],
            ['فاکتور فروش', '/admin/accounting/docs/create?type=sale'],
            ['فاکتور خرید', '/admin/accounting/docs/create?type=purchase'],
            ['پیش‌فاکتور', '/admin/accounting/docs/create?type=proforma'],
            ['سند دستی', '/admin/accounting/docs/create?type=voucher'],
            ['دسته چک', '/admin/accounting/checkbooks'],
            ['چک دریافتی', '/admin/accounting/checks/received'],
            ['چک خرج‌شده', '/admin/accounting/checks/spent'],
            ['اخطار سررسید', '/admin/accounting/checks/alerts'],
            ['مرکز گزارش‌ها', '/admin/accounting/reports'],
            ['تطبیق موجودی', '/admin/accounting/reports/shop-stock'],
            ['فروشگاه', '/products'],
            ['سفارش‌ها', '/admin/orders'],
            ['تیکت‌ها', '/admin/tickets'],
        ];
        $items = '';
        foreach ($links as [$lab, $href]) {
            $items .= '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'" style="display:block;padding:.65rem .8rem;border-radius:12px;background:#0b4f4c;color:#fff;text-decoration:none;margin:.3rem 0">'.$lab.'</a>';
        }

        return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>حسابداری</title><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet"><style>body{font-family:Vazirmatn,Tahoma,sans-serif;background:#f2f6f7;margin:0;padding:1.2rem}h1{color:#0b4f4c}</style></head><body><h1>مدیریت مالی HDD Land</h1><p>داشبورد بدون خطای ۵۰۰ — منوهای حسابداری:</p>'.$items.'</body></html>';
    }

    public function syncShop()
    {
        $n = AccCommerce::syncPendingShopOrders(80);

        return back()->with('success', $n > 0
            ? $n.' سفارش فروشگاه با فاکتور حسابداری هم‌خوان شد.'
            : 'سفارش جدیدی برای همگام‌سازی نبود.');
    }

    public function docs(Request $request)
    {
        $type = (string) $request->query('type', '');
        if (! Schema::hasTable('acc_documents')) {
            return view('accounting::admin.docs', [
                'docs' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30),
                'type' => $type,
                'types' => AccEngine::TYPES,
                'search' => '',
            ]);
        }
        $q = DB::table('acc_documents')->orderByDesc('id');
        if ($type !== '' && isset(AccEngine::TYPES[$type])) {
            $q->where('type', $type);
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('number', 'like', "%{$search}%")
                    ->orWhere('party_name', 'like', "%{$search}%");
            });
        }

        return view('accounting::admin.docs', [
            'docs' => $q->paginate(30)->withQueryString(),
            'type' => $type,
            'types' => AccEngine::TYPES,
            'search' => $search,
        ]);
    }

    public function createDoc(Request $request)
    {
        $type = (string) $request->query('type', 'sale');
        if (! isset(AccEngine::TYPES[$type])) {
            $type = 'sale';
        }

        $safe = function (callable $fn) {
            try {
                return $fn();
            } catch (\Throwable) {
                return collect();
            }
        };

        $accounts = $safe(function () {
            $q = DB::table('acc_accounts')->where('is_active', 1)->orderBy('code');
            if (Schema::hasColumn('acc_accounts', 'is_postable')) {
                $q->where(function ($w) {
                    $w->where('is_postable', 1)->orWhere('level', 'moeen')->orWhere('level', 'tafsil');
                });
            }

            return $q->get();
        });

        return view('accounting::admin.doc-form', [
            'type' => $type,
            'types' => AccEngine::TYPES,
            'warehouses' => $safe(fn () => DB::table('acc_warehouses')->where('is_active', 1)->orderBy('name')->get()),
            'banks' => $safe(fn () => DB::table('acc_banks')->where('is_active', 1)->orderBy('name')->get()),
            'accounts' => $accounts,
            'categories' => $safe(fn () => DB::table('acc_expense_categories')->where('is_active', 1)->orderBy('name')->get()),
            'staff' => $this->staffOptions(),
            'products' => AccCommerce::catalogProducts('', 200),
            'customers' => AccCommerce::customers('', 200),
            'doc' => null,
            'lines' => [],
            'number' => AccEngine::nextNumber($type),
        ]);
    }

    public function storeDoc(Request $request)
    {
        $type = (string) $request->input('type', 'sale');
        if (! isset(AccEngine::TYPES[$type])) {
            return back()->with('error', 'نوع سند نامعتبر است.');
        }

        $titles = (array) $request->input('line_title', []);
        $qtys = (array) $request->input('line_qty', []);
        $prices = (array) $request->input('line_price', []);
        $costs = (array) $request->input('line_cost', []);
        $serials = (array) $request->input('line_serials', []);
        $sides = (array) $request->input('line_side', []);
        $accountIds = (array) $request->input('line_account_id', []);
        $productIds = (array) $request->input('line_product_id', []);
        $skusIn = (array) $request->input('line_sku', []);
        $unitsIn = (array) $request->input('line_unit', []);
        $vatRates = (array) $request->input('line_vat_rate', []);
        $discRates = (array) $request->input('line_discount_rate', []);
        $debits = (array) $request->input('line_debit', []);
        $credits = (array) $request->input('line_credit', []);
        $tafsils = (array) $request->input('line_tafsil', []);

        $lines = [];
        $subtotal = 0;
        $lineDiscount = 0;
        $lineTax = 0;
        $debitSum = 0;
        $creditSum = 0;
        $rowCount = max(count($titles), count($accountIds), count($debits), count($productIds));
        for ($i = 0; $i < $rowCount; $i++) {
            $title = trim((string) ($titles[$i] ?? ''));
            $productId = (int) ($productIds[$i] ?? 0) ?: null;
            $sku = trim((string) ($skusIn[$i] ?? '')) ?: null;
            $unit = trim((string) ($unitsIn[$i] ?? '')) ?: 'عدد';
            $qty = (float) str_replace(',', '', (string) ($qtys[$i] ?? ($type === 'voucher' ? 1 : 1)));
            $price = (int) str_replace(',', '', (string) ($prices[$i] ?? 0));
            $cost = (int) str_replace(',', '', (string) ($costs[$i] ?? 0));
            $vatRate = (float) str_replace(',', '', (string) ($vatRates[$i] ?? 0));
            $discRate = (float) str_replace(',', '', (string) ($discRates[$i] ?? 0));
            $debit = (int) str_replace(',', '', (string) ($debits[$i] ?? 0));
            $credit = (int) str_replace(',', '', (string) ($credits[$i] ?? 0));
            $accountId = (int) ($accountIds[$i] ?? 0) ?: null;
            $tafsil = trim((string) ($tafsils[$i] ?? '')) ?: null;
            if ($productId && Schema::hasTable('products')) {
                $p = DB::table('products')->where('id', $productId)->first();
                if ($p) {
                    $title = $title !== '' ? $title : (string) ($p->name ?? '');
                    $sku = $sku ?: ($p->sku ?? null);
                    if ($price <= 0) {
                        $price = (int) ($p->price ?? 0);
                    }
                    if ($cost <= 0) {
                        $cost = (int) ($p->cost_price ?? 0);
                    }
                    if ($vatRate <= 0 && isset($p->vat_rate)) {
                        $vatRate = (float) $p->vat_rate;
                    }
                    if (($unitsIn[$i] ?? '') === '' && ! empty($p->unit)) {
                        $unit = (string) $p->unit;
                    }
                }
            }
            if ($type === 'voucher') {
                if ($debit <= 0 && $credit <= 0 && ! $accountId && $title === '') {
                    continue;
                }
                if ($debit > 0 && $credit > 0) {
                    return back()->withInput()->with('error', 'در هر سطر سند فقط بدهکار یا بستانکار وارد شود.');
                }
                if ($accountId < 1 || ($debit + $credit) <= 0) {
                    continue;
                }
                $side = $credit > 0 ? 'credit' : 'debit';
                $amount = $debit + $credit;
                $debitSum += $debit;
                $creditSum += $credit;
                $lines[] = [
                    'title' => $title !== '' ? $title : 'ماده سند',
                    'sku' => $sku,
                    'qty' => 1,
                    'unit_price' => $debit,
                    'unit_cost' => $credit,
                    'line_total' => $amount,
                    'side' => $side,
                    'account_id' => $accountId,
                    'tafsil' => $tafsil,
                    'serials' => [],
                ];
                $subtotal += $amount;

                continue;
            }
            if ($title === '') {
                continue;
            }
            $gross = (int) round($qty * $price);
            $discAmt = (int) round($gross * max(0, $discRate) / 100);
            $afterDisc = max(0, $gross - $discAmt);
            $vatAmt = (int) round($afterDisc * max(0, $vatRate) / 100);
            $lineTotal = $afterDisc + $vatAmt;
            $subtotal += $gross;
            $lineDiscount += $discAmt;
            $lineTax += $vatAmt;
            $snRaw = (string) ($serials[$i] ?? '');
            $snList = preg_split('/[\s,;]+/u', $snRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $lines[] = [
                'product_id' => $productId,
                'title' => $title,
                'sku' => $sku,
                'unit' => $unit,
                'qty' => $qty,
                'unit_price' => $price,
                'unit_cost' => $cost,
                'vat_rate' => $vatRate,
                'discount_rate' => $discRate,
                'line_total' => $lineTotal,
                'side' => $sides[$i] ?? null,
                'account_id' => $accountId,
                'serials' => $snList,
            ];
        }

        if ($type === 'voucher') {
            if ($lines === [] || $debitSum !== $creditSum || $debitSum <= 0) {
                return back()->withInput()->with('error', 'سند دستی باید حداقل دو ماده داشته باشد و جمع بدهکار با بستانکار برابر باشد.');
            }
        }

        if ($lines === [] && $type !== 'expense') {
            return back()->withInput()->with('error', 'حداقل یک قلم کالا/سطر لازم است.');
        }

        if ($type === 'expense' && $lines === []) {
            $amount = (int) str_replace(',', '', (string) $request->input('total', 0));
            $lines[] = [
                'title' => (string) $request->input('party_name', 'هزینه'),
                'qty' => 1,
                'unit_price' => $amount,
                'unit_cost' => 0,
                'line_total' => $amount,
                'serials' => [],
            ];
            $subtotal = $amount;
        }

        $discount = (int) str_replace(',', '', (string) $request->input('discount', 0)) + $lineDiscount;
        $tax = (int) str_replace(',', '', (string) $request->input('tax', 0)) + $lineTax;
        $total = $type === 'voucher' ? $debitSum : max(0, $subtotal - $discount + $tax);
        $staffId = $request->filled('staff_id') ? (int) $request->input('staff_id') : null;
        $rate = (float) $request->input('commission_rate', 0);
        if ($staffId && $rate <= 0) {
            $staff = $this->staffOptions()->firstWhere('id', $staffId);
            if ($staff) {
                $kind = (string) ($staff->kind ?? 'employee');
                $rate = $kind === 'visitor'
                    ? (float) ($staff->profit_rate ?? 0)
                    : (float) ($staff->commission_rate ?? 0);
            }
        }
        $commissionBase = $total;
        if ($staffId) {
            $staff = $this->staffOptions()->firstWhere('id', $staffId);
            if ($staff && (string) ($staff->kind ?? '') === 'visitor') {
                $cogs = 0;
                foreach ($lines as $ln) {
                    $cogs += (int) round(((float) ($ln['qty'] ?? 1)) * ((int) ($ln['unit_cost'] ?? 0)));
                }
                $commissionBase = max(0, $total - $cogs);
            }
        }
        $commission = (int) round($commissionBase * $rate / 100);

        $partyUserId = $request->filled('party_user_id') ? (int) $request->input('party_user_id') : null;
        $partyName = trim((string) $request->input('party_name', ''));
        if ($partyUserId && $partyName === '') {
            $partyName = AccCommerce::userName($partyUserId) ?: $partyName;
        }

        $id = AccEngine::createDocument([
            'type' => $type,
            'status' => $request->boolean('issue_now') ? 'draft' : 'draft',
            'doc_date' => $request->input('doc_date') ?: now()->toDateString(),
            'party_name' => $partyName !== '' ? $partyName : null,
            'party_user_id' => $partyUserId,
            'source' => 'manual',
            'warehouse_id' => $request->input('warehouse_id') ?: AccCommerce::defaultWarehouseId(),
            'warehouse_to_id' => $request->input('warehouse_to_id') ?: null,
            'bank_id' => $request->input('bank_id') ?: null,
            'staff_id' => $request->input('staff_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'payment_method' => $request->input('payment_method'),
            'notes' => $request->input('notes'),
        ], $lines);

        if ($request->boolean('issue_now')) {
            AccEngine::issueDocument($id);
        }

        return redirect(url('/admin/accounting/docs/'.$id))->with('success', 'سند ذخیره شد.');
    }

    public function showDoc(int $id)
    {
        $doc = DB::table('acc_documents')->where('id', $id)->first();
        abort_unless($doc, 404);
        $lines = DB::table('acc_document_lines')->where('document_id', $id)->get();
        $serials = DB::table('acc_document_serials')->where('document_id', $id)->get();

        $order = null;
        if (! empty($doc->order_id) && Schema::hasTable('orders')) {
            $order = DB::table('orders')->where('id', $doc->order_id)->first();
        }

        return view('accounting::admin.doc-show', compact('doc', 'lines', 'serials', 'order') + [
            'types' => AccEngine::TYPES,
        ]);
    }

    public function issueDoc(int $id)
    {
        AccEngine::issueDocument($id);

        return back()->with('success', 'سند صادر و موجودی به‌روز شد.');
    }

    public function convertProforma(int $id)
    {
        $newId = AccEngine::convertProforma($id);
        if (! $newId) {
            return back()->with('error', 'پیش‌فاکتور یافت نشد.');
        }

        return redirect(url('/admin/accounting/docs/'.$newId))->with('success', 'به فاکتور فروش تبدیل شد.');
    }

    public function warehouses()
    {
        return view('accounting::admin.warehouses', [
            'items' => DB::table('acc_warehouses')->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function storeWarehouse(Request $request)
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $name = trim((string) $request->input('name'));
        if ($code === '' || $name === '') {
            return back()->with('error', 'کد و نام انبار الزامی است.');
        }
        if ($request->boolean('is_default')) {
            DB::table('acc_warehouses')->update(['is_default' => false]);
        }
        DB::table('acc_warehouses')->insert([
            'code' => $code,
            'name' => $name,
            'city' => $request->input('city'),
            'address' => $request->input('address'),
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'انبار ثبت شد.');
    }

    public function banks()
    {
        return view('accounting::admin.banks', [
            'items' => DB::table('acc_banks')->orderBy('name')->get(),
        ]);
    }

    public function storeBank(Request $request)
    {
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return back()->with('error', 'نام بانک الزامی است.');
        }
        DB::table('acc_banks')->insert([
            'name' => $name,
            'branch' => $request->input('branch'),
            'account_no' => $request->input('account_no', $request->input('account_no')),
            'iban' => $request->input('iban'),
            'card_no' => $request->input('card_no', $request->input('card_no')),
            'holder' => $request->input('holder', $request->input('holder')),
            'opening_balance' => (int) str_replace(',', '', (string) $request->input('opening_balance', $request->input('opening_balance', 0))),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'حساب بانکی ثبت شد.');
    }

    public function stock()
    {
        $balances = DB::table('acc_stock_balances as b')
            ->leftJoin('acc_warehouses as w', 'w.id', '=', 'b.warehouse_id')
            ->orderBy('w.name')
            ->select('b.*', 'w.name as warehouse_name', 'w.code as warehouse_code');
        if (Schema::hasTable('products')) {
            $balances->leftJoin('products as p', 'p.id', '=', 'b.product_id')
                ->addSelect(DB::raw('COALESCE(p.name, CONCAT("کالا #", b.product_id)) as product_name'));
        }
        $balances = $balances->limit(200)->get();

        return view('accounting::admin.stock', [
            'balances' => $balances,
            'warehouses' => DB::table('acc_warehouses')->where('is_active', 1)->get(),
            'products' => AccCommerce::catalogProducts('', 120),
            'moves' => DB::table('acc_documents')->whereIn('type', ['stock_in', 'stock_out', 'transfer'])->orderByDesc('id')->limit(40)->get(),
        ]);
    }

    public function storeStockMove(Request $request)
    {
        $type = (string) $request->input('type', 'stock_in');
        if (! in_array($type, ['stock_in', 'stock_out', 'transfer'], true)) {
            return back()->with('error', 'نوع حرکت انبار نامعتبر است.');
        }
        $title = trim((string) $request->input('title', 'کالا'));
        $qty = (float) str_replace(',', '', (string) $request->input('qty', 1));
        $cost = (int) str_replace(',', '', (string) $request->input('unit_cost', 0));
        $productId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        if ($productId && Schema::hasTable('products')) {
            $p = DB::table('products')->where('id', $productId)->first();
            if ($p) {
                if ($title === '' || $title === 'کالا') {
                    $title = (string) ($p->name ?? $title);
                }
                if ($cost <= 0) {
                    $cost = (int) ($p->cost_price ?? 0);
                }
            }
        }
        if ($title === '' || $qty <= 0) {
            return back()->with('error', 'نام کالا و تعداد الزامی است.');
        }
        $serials = preg_split('/[\s,;]+/u', (string) $request->input('serials', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $id = AccEngine::createDocument([
            'type' => $type,
            'status' => 'draft',
            'doc_date' => $request->input('doc_date') ?: now()->toDateString(),
            'warehouse_id' => $request->input('warehouse_id') ?: null,
            'warehouse_to_id' => $request->input('warehouse_to_id') ?: null,
            'party_name' => $request->input('party_name'),
            'notes' => $request->input('notes'),
            'subtotal' => (int) round($qty * $cost),
            'total' => (int) round($qty * $cost),
        ], [[
            'title' => $title,
            'qty' => $qty,
            'unit_price' => $cost,
            'unit_cost' => $cost,
            'line_total' => (int) round($qty * $cost),
            'product_id' => $productId,
            'serials' => $serials,
        ]]);
        if ($request->boolean('issue_now', true)) {
            AccEngine::issueDocument($id);
        }

        return back()->with('success', 'حرکت انبار ثبت شد.');
    }

    public function expenses()
    {
        return view('accounting::admin.expenses', [
            'items' => DB::table('acc_documents')->where('type', 'expense')->orderByDesc('id')->limit(100)->get(),
            'categories' => DB::table('acc_expense_categories')->where('is_active', 1)->get(),
            'banks' => DB::table('acc_banks')->where('is_active', 1)->get(),
        ]);
    }

    public function storeExpense(Request $request)
    {
        $amount = (int) str_replace(',', '', (string) $request->input('amount', 0));
        if ($amount <= 0) {
            return back()->with('error', 'مبلغ هزینه نامعتبر است.');
        }
        $title = trim((string) $request->input('title', 'هزینه'));
        $id = AccEngine::createDocument([
            'type' => 'expense',
            'status' => 'draft',
            'doc_date' => $request->input('doc_date') ?: now()->toDateString(),
            'party_name' => $title,
            'category_id' => $request->input('category_id') ?: null,
            'bank_id' => $request->input('bank_id') ?: null,
            'subtotal' => $amount,
            'total' => $amount,
            'notes' => $request->input('notes'),
            'payment_method' => $request->input('payment_method'),
        ], [[
            'title' => $title,
            'qty' => 1,
            'unit_price' => $amount,
            'line_total' => $amount,
            'serials' => [],
        ]]);
        if ($request->boolean('issue_now')) {
            AccEngine::issueDocument($id);
        }

        return back()->with('success', 'هزینه ثبت شد.');
    }

    public function payroll()
    {
        $runs = collect();
        try {
            if (Schema::hasTable('acc_payroll_runs')) {
                $runs = DB::table('acc_payroll_runs')->orderByDesc('id')->limit(24)->get();
            }
        } catch (\Throwable) {
        }

        return view('accounting::admin.payroll', [
            'runs' => $runs,
            'staff' => $this->staffOptions(),
            'period' => now()->format('Y-m'),
        ]);
    }

    public function storePayroll(Request $request)
    {
        $period = (string) $request->input('period', now()->format('Y-m'));
        $staffRows = $this->staffOptions();
        $runId = (int) DB::table('acc_payroll_runs')->insertGetId([
            'period' => $period,
            'status' => 'draft',
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tb = $tc = $td = $tn = 0;
        foreach ($staffRows as $s) {
            $base = (int) ($s->base_salary ?? 0);
            $kind = (string) ($s->kind ?? 'employee');
            $rate = $kind === 'visitor'
                ? (float) ($s->profit_rate ?? $s->commission_rate ?? 0)
                : (float) ($s->commission_rate ?? 0);
            $commission = 0;
            try {
                $docsQ = DB::table('acc_documents')
                    ->where('type', 'sale')
                    ->where('staff_id', $s->id)
                    ->where('status', 'issued')
                    ->where('doc_date', 'like', $period.'%');
                if ($kind === 'visitor') {
                    $sales = (int) (clone $docsQ)->sum('total');
                    $cogs = 0;
                    if (Schema::hasTable('acc_document_lines')) {
                        $ids = (clone $docsQ)->pluck('id');
                        if ($ids->isNotEmpty()) {
                            $cogs = (int) DB::table('acc_document_lines')
                                ->whereIn('document_id', $ids)
                                ->selectRaw('COALESCE(SUM(qty * unit_cost),0) as c')
                                ->value('c');
                        }
                    }
                    $commission = (int) round(max(0, $sales - $cogs) * $rate / 100);
                } else {
                    $commission = (int) $docsQ->sum('commission_amount');
                }
            } catch (\Throwable) {
            }
            $deduction = 0;
            $net = $base + $commission - $deduction;
            DB::table('acc_payslips')->insert([
                'payroll_run_id' => $runId,
                'staff_id' => $s->id,
                'staff_name' => $s->name,
                'base_salary' => $base,
                'commission_rate' => $rate,
                'commission_amount' => $commission,
                'deduction' => $deduction,
                'net' => $net,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $tb += $base; $tc += $commission; $td += $deduction; $tn += $net;
        }
        DB::table('acc_payroll_runs')->where('id', $runId)->update([
            'total_base' => $tb,
            'total_commission' => $tc,
            'total_deduction' => $td,
            'total_net' => $tn,
            'status' => 'calculated',
            'updated_at' => now(),
        ]);

        return redirect(url('/admin/accounting/payroll/'.$runId))->with('success', 'لیست حقوق محاسبه شد.');
    }

    public function showPayroll(int $id)
    {
        $run = DB::table('acc_payroll_runs')->where('id', $id)->first();
        abort_unless($run, 404);

        return view('accounting::admin.payroll-show', [
            'run' => $run,
            'slips' => DB::table('acc_payslips')->where('payroll_run_id', $id)->orderBy('staff_name')->get(),
        ]);
    }

    public function commissions()
    {
        return redirect(url('/admin/accounting/staff'))->with('success', 'تعریف کمیسیون و درصد سود خالص الان در منوی کارمند و ویزیتور است.');
    }

    public function updateCommission(Request $request, int $staffId)
    {
        $rate = (float) $request->input('commission_rate', 0);
        if (Schema::hasTable('staff_members')) {
            DB::table('staff_members')->where('id', $staffId)->update([
                'commission_rate' => max(0, min(100, $rate)),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'درصد کمیسیون به‌روز شد.');
    }

    public function goods()
    {
        $products = AccCommerce::catalogProducts('', 300);

        return view('accounting::admin.goods', [
            'products' => $products,
        ]);
    }

    public function storeGood(Request $request)
    {
        if (! Schema::hasTable('products')) {
            return back()->with('error', 'جدول کالاهای فروشگاه آماده نیست.');
        }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return back()->with('error', 'نام کالا الزامی است.');
        }
        $row = [
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $map = [
            'sku' => trim((string) $request->input('sku', '')) ?: null,
            'barcode' => trim((string) $request->input('barcode', '')) ?: null,
            'unit' => trim((string) $request->input('unit', '')) ?: 'عدد',
            'price' => (int) str_replace(',', '', (string) $request->input('price', 0)),
            'cost_price' => (int) str_replace(',', '', (string) $request->input('cost_price', 0)),
            'vat_rate' => (float) $request->input('vat_rate', 0),
            'min_qty' => (float) $request->input('min_qty', 0),
            'stock' => (float) $request->input('stock', 0),
        ];
        foreach ($map as $col => $val) {
            if (Schema::hasColumn('products', $col)) {
                $row[$col] = $val;
            }
        }
        if (Schema::hasColumn('products', 'status')) {
            $row['status'] = $request->boolean('is_active', true) ? 'publish' : 'draft';
        }
        if (Schema::hasColumn('products', 'stock_status')) {
            $row['stock_status'] = ((float) ($row['stock'] ?? 0)) > 0 ? 'instock' : 'outofstock';
        }
        if ($request->filled('id')) {
            $id = (int) $request->input('id');
            unset($row['created_at']);
            DB::table('products')->where('id', $id)->update($row);

            return back()->with('success', 'کالا به‌روز شد.');
        }
        DB::table('products')->insert($row);

        return back()->with('success', 'کالا در فروشگاه و حسابداری ثبت شد.');
    }

    public function reports(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        if (! Schema::hasTable('acc_documents')) {
            return view('accounting::admin.reports', [
                'from' => $from, 'to' => $to, 'sales' => 0, 'purchase' => 0, 'expense' => 0,
                'commission' => 0, 'byType' => collect(), 'stockValue' => 0, 'menuReport' => [],
                'daily' => collect(), 'types' => AccEngine::TYPES, 'profit' => 0,
            ]);
        }
        $sales = (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $purchase = (int) DB::table('acc_documents')->where('type', 'purchase')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $expense = (int) DB::table('acc_documents')->where('type', 'expense')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $commission = (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('commission_amount');
        $byType = DB::table('acc_documents')
            ->select('type', DB::raw('count(*) as c'), DB::raw('sum(total) as s'))
            ->whereBetween('doc_date', [$from, $to])
            ->groupBy('type')->get();
        $stockValue = 0;
        try {
            $stockValue = (int) DB::table('acc_stock_balances')->selectRaw('COALESCE(SUM(qty * avg_cost),0) as v')->value('v');
        } catch (\Throwable) {
        }
        $menuReport = [
            ['key' => 'sale', 'label' => 'فروش', 'count' => (int) DB::table('acc_documents')->where('type', 'sale')->whereBetween('doc_date', [$from, $to])->count(), 'total' => $sales],
            ['key' => 'purchase', 'label' => 'خرید', 'count' => (int) DB::table('acc_documents')->where('type', 'purchase')->whereBetween('doc_date', [$from, $to])->count(), 'total' => $purchase],
            ['key' => 'proforma', 'label' => 'پیش‌فاکتور', 'count' => (int) DB::table('acc_documents')->where('type', 'proforma')->whereBetween('doc_date', [$from, $to])->count(), 'total' => (int) DB::table('acc_documents')->where('type', 'proforma')->whereBetween('doc_date', [$from, $to])->sum('total')],
            ['key' => 'voucher', 'label' => 'سند دستی', 'count' => (int) DB::table('acc_documents')->where('type', 'voucher')->whereBetween('doc_date', [$from, $to])->count(), 'total' => (int) DB::table('acc_documents')->where('type', 'voucher')->whereBetween('doc_date', [$from, $to])->sum('total')],
            ['key' => 'expense', 'label' => 'هزینه', 'count' => (int) DB::table('acc_documents')->where('type', 'expense')->whereBetween('doc_date', [$from, $to])->count(), 'total' => $expense],
            ['key' => 'stock', 'label' => 'انبار', 'count' => (int) DB::table('acc_documents')->whereIn('type', ['stock_in', 'stock_out', 'transfer'])->whereBetween('doc_date', [$from, $to])->count(), 'total' => $stockValue],
            ['key' => 'payroll', 'label' => 'حقوق', 'count' => (int) (Schema::hasTable('acc_payroll_runs') ? DB::table('acc_payroll_runs')->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count() : 0), 'total' => (int) (Schema::hasTable('acc_payroll_runs') ? DB::table('acc_payroll_runs')->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->sum('total_net') : 0)],
            ['key' => 'commission', 'label' => 'کمیسیون', 'count' => (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->where('commission_amount', '>', 0)->whereBetween('doc_date', [$from, $to])->count(), 'total' => $commission],
            ['key' => 'banks', 'label' => 'بانک‌ها', 'count' => (int) DB::table('acc_banks')->where('is_active', 1)->count(), 'total' => (int) DB::table('acc_banks')->sum('opening_balance')],
            ['key' => 'warehouses', 'label' => 'انبارها', 'count' => (int) DB::table('acc_warehouses')->where('is_active', 1)->count(), 'total' => $stockValue],
        ];
        $daily = DB::table('acc_documents')
            ->select('doc_date', DB::raw("sum(case when type='sale' and status='issued' then total else 0 end) as sales"), DB::raw("sum(case when type='purchase' and status='issued' then total else 0 end) as purchase"), DB::raw("sum(case when type='expense' and status='issued' then total else 0 end) as expense"))
            ->whereBetween('doc_date', [$from, $to])
            ->groupBy('doc_date')
            ->orderBy('doc_date')
            ->get();

        return view('accounting::admin.reports', compact('from', 'to', 'sales', 'purchase', 'expense', 'commission', 'byType', 'stockValue', 'menuReport', 'daily') + [
            'types' => AccEngine::TYPES,
            'profit' => $sales - $purchase - $expense - $commission,
        ]);
    }

    /* ─── CRUD: warehouses ─── */
    public function updateWarehouse(Request $request, int $id)
    {
        $row = DB::table('acc_warehouses')->where('id', $id)->first();
        abort_unless($row, 404);
        $code = strtoupper(trim((string) $request->input('code', $row->code)));
        $name = trim((string) $request->input('name', $row->name));
        if ($code === '' || $name === '') {
            return back()->with('error', 'کد و نام انبار الزامی است.');
        }
        if ($request->boolean('is_default')) {
            DB::table('acc_warehouses')->update(['is_default' => false]);
        }
        DB::table('acc_warehouses')->where('id', $id)->update([
            'code' => $code,
            'name' => $name,
            'city' => $request->input('city'),
            'address' => $request->input('address'),
            'is_default' => $request->boolean('is_default') || (bool) $row->is_default,
            'is_active' => $request->boolean('is_active', (bool) $row->is_active),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'انبار ویرایش شد.');
    }

    public function deleteWarehouse(int $id)
    {
        $row = DB::table('acc_warehouses')->where('id', $id)->first();
        abort_unless($row, 404);
        if ($row->is_default) {
            return back()->with('error', 'انبار پیش‌فرض را نمی‌توان حذف کرد — ابتدا انبار دیگری را پیش‌فرض کنید.');
        }
        $used = DB::table('acc_documents')->where('warehouse_id', $id)->orWhere('warehouse_to_id', $id)->exists()
            || (Schema::hasTable('acc_stock_balances') && DB::table('acc_stock_balances')->where('warehouse_id', $id)->where('qty', '!=', 0)->exists());
        if ($used) {
            DB::table('acc_warehouses')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);

            return back()->with('success', 'انبار به‌خاطر سابقه سند غیرفعال شد.');
        }
        DB::table('acc_warehouses')->where('id', $id)->delete();

        return back()->with('success', 'انبار حذف شد.');
    }

    /* ─── CRUD: banks ─── */
    public function updateBank(Request $request, int $id)
    {
        $row = DB::table('acc_banks')->where('id', $id)->first();
        abort_unless($row, 404);
        $name = trim((string) $request->input('name', $row->name));
        if ($name === '') {
            return back()->with('error', 'نام بانک الزامی است.');
        }
        DB::table('acc_banks')->where('id', $id)->update([
            'name' => $name,
            'branch' => $request->input('branch'),
            'account_no' => $request->input('account_no'),
            'iban' => $request->input('iban'),
            'card_no' => $request->input('card_no'),
            'holder' => $request->input('holder'),
            'opening_balance' => (int) str_replace(',', '', (string) $request->input('opening_balance', $row->opening_balance)),
            'is_active' => $request->boolean('is_active', (bool) $row->is_active),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'بانک ویرایش شد.');
    }

    public function deleteBank(int $id)
    {
        $used = DB::table('acc_documents')->where('bank_id', $id)->exists();
        if ($used) {
            DB::table('acc_banks')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);

            return back()->with('success', 'بانک به‌خاطر سابقه سند غیرفعال شد.');
        }
        DB::table('acc_banks')->where('id', $id)->delete();

        return back()->with('success', 'بانک حذف شد.');
    }

    /* ─── CRUD: expense categories + chart accounts ─── */
    public function settings()
    {
        return view('accounting::admin.settings', [
            'categories' => DB::table('acc_expense_categories')->orderBy('name')->get(),
            'accounts' => DB::table('acc_accounts')->orderBy('code')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return back()->with('error', 'نام دسته الزامی است.');
        }
        DB::table('acc_expense_categories')->insert([
            'name' => $name,
            'code' => $request->input('code') ?: null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'دسته هزینه ثبت شد.');
    }

    public function updateCategory(Request $request, int $id)
    {
        DB::table('acc_expense_categories')->where('id', $id)->update([
            'name' => trim((string) $request->input('name')),
            'code' => $request->input('code'),
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'دسته هزینه ویرایش شد.');
    }

    public function deleteCategory(int $id)
    {
        $used = DB::table('acc_documents')->where('category_id', $id)->exists();
        if ($used) {
            DB::table('acc_expense_categories')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);

            return back()->with('success', 'دسته غیرفعال شد.');
        }
        DB::table('acc_expense_categories')->where('id', $id)->delete();

        return back()->with('success', 'دسته حذف شد.');
    }

    public function storeAccount(Request $request)
    {
        $code = trim((string) $request->input('code'));
        $name = trim((string) $request->input('name'));
        if ($code === '' || $name === '') {
            return back()->with('error', 'کد و نام حساب الزامی است.');
        }
        $row = [
            'code' => $code,
            'name' => $name,
            'type' => $request->input('type', 'expense'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('acc_accounts', 'is_postable')) {
            $row['is_postable'] = true;
            $row['level'] = 'moeen';
            $row['is_system'] = false;
        }
        DB::table('acc_accounts')->insert($row);

        return back()->with('success', 'حساب ثبت شد.');
    }

    public function updateAccount(Request $request, int $id)
    {
        DB::table('acc_accounts')->where('id', $id)->update([
            'code' => trim((string) $request->input('code')),
            'name' => trim((string) $request->input('name')),
            'type' => $request->input('type', 'expense'),
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'حساب ویرایش شد.');
    }

    public function deleteAccount(int $id)
    {
        DB::table('acc_accounts')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);

        return back()->with('success', 'حساب غیرفعال شد.');
    }

    /* ─── Document cancel / delete ─── */
    public function cancelDoc(int $id)
    {
        if (! AccEngine::cancelDocument($id)) {
            return back()->with('error', 'امکان ابطال این سند نیست.');
        }

        return back()->with('success', 'سند ابطال شد و موجودی در صورت نیاز برگشت خورد.');
    }

    public function deleteDoc(int $id)
    {
        if (! AccEngine::deleteDocument($id)) {
            return back()->with('error', 'فقط پیش‌نویس/ابطال‌شده قابل حذف قطعی است.');
        }

        return redirect(url('/admin/accounting/docs'))->with('success', 'سند حذف شد.');
    }

    public function deletePayroll(int $id)
    {
        $run = DB::table('acc_payroll_runs')->where('id', $id)->first();
        abort_unless($run, 404);
        if ($run->status === 'paid') {
            return back()->with('error', 'لیست پرداخت‌شده قابل حذف نیست.');
        }
        DB::table('acc_payslips')->where('payroll_run_id', $id)->delete();
        DB::table('acc_payroll_runs')->where('id', $id)->delete();

        return redirect(url('/admin/accounting/payroll'))->with('success', 'لیست حقوق حذف شد.');
    }

    /** @return \Illuminate\Support\Collection<int,object> */
    protected function staffOptions()
    {
        try {
            if (Schema::hasTable('staff_members')) {
                return DB::table('staff_members')->where('is_active', 1)->orderBy('name')->get();
            }
        } catch (\Throwable) {
        }

        return collect();
    }
}
