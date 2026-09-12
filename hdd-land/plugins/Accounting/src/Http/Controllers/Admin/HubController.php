<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;

class HubController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
        Plugin::seedDefaults();
    }

    public function hub()
    {
        return view('accounting::admin.hub', [
            'stats' => AccEngine::dashboardStats(),
            'types' => AccEngine::TYPES,
            'recent' => DB::table('acc_documents')->orderByDesc('id')->limit(12)->get(),
        ]);
    }

    public function docs(Request $request)
    {
        $type = (string) $request->query('type', '');
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

        return view('accounting::admin.doc-form', [
            'type' => $type,
            'types' => AccEngine::TYPES,
            'warehouses' => DB::table('acc_warehouses')->where('is_active', 1)->orderBy('name')->get(),
            'banks' => DB::table('acc_banks')->where('is_active', 1)->orderBy('name')->get(),
            'accounts' => DB::table('acc_accounts')->where('is_active', 1)->orderBy('code')->get(),
            'categories' => DB::table('acc_expense_categories')->where('is_active', 1)->orderBy('name')->get(),
            'staff' => $this->staffOptions(),
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

        $lines = [];
        $subtotal = 0;
        foreach ($titles as $i => $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            $qty = (float) str_replace(',', '', (string) ($qtys[$i] ?? 1));
            $price = (int) str_replace(',', '', (string) ($prices[$i] ?? 0));
            $cost = (int) str_replace(',', '', (string) ($costs[$i] ?? 0));
            $lineTotal = (int) round($qty * $price);
            $subtotal += $lineTotal;
            $snRaw = (string) ($serials[$i] ?? '');
            $snList = preg_split('/[\s,;]+/u', $snRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $lines[] = [
                'title' => $title,
                'qty' => $qty,
                'unit_price' => $price,
                'unit_cost' => $cost,
                'line_total' => $lineTotal,
                'side' => $sides[$i] ?? null,
                'account_id' => $accountIds[$i] ?? null,
                'serials' => $snList,
            ];
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

        $discount = (int) str_replace(',', '', (string) $request->input('discount', 0));
        $tax = (int) str_replace(',', '', (string) $request->input('tax', 0));
        $total = max(0, $subtotal - $discount + $tax);
        $rate = (float) $request->input('commission_rate', 0);
        $commission = (int) round($total * $rate / 100);

        $id = AccEngine::createDocument([
            'type' => $type,
            'status' => $request->boolean('issue_now') ? 'draft' : 'draft',
            'doc_date' => $request->input('doc_date') ?: now()->toDateString(),
            'party_name' => $request->input('party_name'),
            'party_user_id' => $request->input('party_user_id') ?: null,
            'warehouse_id' => $request->input('warehouse_id') ?: null,
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

        return redirect()->route('admin.accounting.doc', $id)->with('success', 'سند ذخیره شد.');
    }

    public function showDoc(int $id)
    {
        $doc = DB::table('acc_documents')->where('id', $id)->first();
        abort_unless($doc, 404);
        $lines = DB::table('acc_document_lines')->where('document_id', $id)->get();
        $serials = DB::table('acc_document_serials')->where('document_id', $id)->get();

        return view('accounting::admin.doc-show', compact('doc', 'lines', 'serials') + [
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

        return redirect()->route('admin.accounting.doc', $newId)->with('success', 'به فاکتور فروش تبدیل شد.');
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
            'account_no' => $request->input('account_no'),
            'iban' => $request->input('iban'),
            'card_no' => $request->input('card_no'),
            'holder' => $request->input('holder'),
            'opening_balance' => (int) str_replace(',', '', (string) $request->input('opening_balance', 0)),
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
            ->select('b.*', 'w.name as warehouse_name', 'w.code as warehouse_code')
            ->limit(200)
            ->get();

        return view('accounting::admin.stock', [
            'balances' => $balances,
            'warehouses' => DB::table('acc_warehouses')->where('is_active', 1)->get(),
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
            'product_id' => $request->input('product_id') ?: null,
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
        return view('accounting::admin.payroll', [
            'runs' => DB::table('acc_payroll_runs')->orderByDesc('id')->limit(24)->get(),
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
            $rate = (float) ($s->commission_rate ?? 0);
            // commission estimate from issued sales attributed to staff this period
            $commission = 0;
            try {
                $commission = (int) DB::table('acc_documents')
                    ->where('type', 'sale')
                    ->where('staff_id', $s->id)
                    ->where('status', 'issued')
                    ->where('doc_date', 'like', $period.'%')
                    ->sum('commission_amount');
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

        return redirect()->route('admin.accounting.payroll.show', $runId)->with('success', 'لیست حقوق محاسبه شد.');
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
        $staff = $this->staffOptions();
        $rows = [];
        foreach ($staff as $s) {
            $sales = (int) DB::table('acc_documents')->where('type', 'sale')->where('staff_id', $s->id)->where('status', 'issued')->sum('total');
            $comm = (int) DB::table('acc_documents')->where('type', 'sale')->where('staff_id', $s->id)->where('status', 'issued')->sum('commission_amount');
            $rows[] = (object) [
                'id' => $s->id,
                'name' => $s->name,
                'role' => $s->role ?? '',
                'rate' => (float) ($s->commission_rate ?? 0),
                'sales' => $sales,
                'commission' => $comm,
            ];
        }

        return view('accounting::admin.commissions', ['rows' => $rows]);
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

    public function reports(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $sales = (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $purchase = (int) DB::table('acc_documents')->where('type', 'purchase')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $expense = (int) DB::table('acc_documents')->where('type', 'expense')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
        $commission = (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('commission_amount');
        $byType = DB::table('acc_documents')
            ->select('type', DB::raw('count(*) as c'), DB::raw('sum(total) as s'))
            ->whereBetween('doc_date', [$from, $to])
            ->groupBy('type')->get();
        $stockValue = (int) DB::table('acc_stock_balances')->selectRaw('COALESCE(SUM(qty * avg_cost),0) as v')->value('v');

        return view('accounting::admin.reports', compact('from', 'to', 'sales', 'purchase', 'expense', 'commission', 'byType', 'stockValue') + [
            'types' => AccEngine::TYPES,
        ]);
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
