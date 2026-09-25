<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccChart;
use Plugins\Accounting\src\Support\AccJournal;
use Plugins\Accounting\src\Support\AccSafe;

class ChartController
{
    public function __construct()
    {
        try {
            Plugin::ensureSchema();
            AccChart::seed();
        } catch (\Throwable) {
        }
    }

    public function index(Request $request)
    {
        return AccSafe::wrap('accounting::admin.chart', function () use ($request) {
            $accounts = collect();
            if (Schema::hasTable('acc_accounts')) {
                $q = DB::table('acc_accounts')->orderBy('code');
                if (! $request->boolean('all')) {
                    $q->where('is_active', 1);
                }
                $accounts = $q->get();
            }
            $parents = $accounts->where('is_postable', false)->values();
            if ($parents->isEmpty()) {
                $parents = $accounts->filter(fn ($a) => strlen((string) $a->code) <= 2)->values();
            }

            return [
                'accounts' => $accounts,
                'parents' => $parents,
                'types' => AccChart::TYPES,
                'levels' => AccChart::LEVELS,
                'showAll' => $request->boolean('all'),
                'map' => AccChart::defaultMap(),
            ];
        }, 'کدینگ حساب‌ها');
    }

    public function store(Request $request)
    {
        $code = preg_replace('/\s+/', '', (string) $request->input('code', ''));
        $name = trim((string) $request->input('name', ''));
        if ($code === '' || $name === '') {
            return back()->with('error', 'کد و عنوان حساب لازم است.');
        }
        if (DB::table('acc_accounts')->where('code', $code)->exists()) {
            return back()->with('error', 'این کد قبلاً ثبت شده.');
        }
        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        $parent = $parentId ? DB::table('acc_accounts')->where('id', $parentId)->first() : null;
        if ($parent && ! str_starts_with($code, (string) $parent->code)) {
            return back()->with('error', 'کد معین باید با کد حساب بالاتر شروع شود.');
        }
        $level = (string) $request->input('level', $parent ? 'moeen' : 'kol');
        DB::table('acc_accounts')->insert([
            'code' => $code,
            'name' => $name,
            'type' => $request->input('type', $parent->type ?? 'expense'),
            'parent_id' => $parentId,
            'level' => $level,
            'nature' => $request->input('nature', 'debit'),
            'is_postable' => $request->boolean('is_postable', true),
            'is_system' => false,
            'is_active' => true,
            'sort' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'حساب '.$code.' اضافه شد.');
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('acc_accounts')->where('id', $id)->first();
        abort_unless($row, 404);
        $name = trim((string) $request->input('name', $row->name));
        if ($name === '') {
            return back()->with('error', 'عنوان خالی است.');
        }
        $data = [
            'name' => $name,
            'is_active' => $request->boolean('is_active', (bool) $row->is_active),
            'updated_at' => now(),
        ];
        if (empty($row->is_system)) {
            $data['type'] = $request->input('type', $row->type);
            $data['nature'] = $request->input('nature', $row->nature ?? 'debit');
            $data['is_postable'] = $request->boolean('is_postable', (bool) ($row->is_postable ?? false));
        }
        DB::table('acc_accounts')->where('id', $id)->update($data);

        return back()->with('success', 'حساب ویرایش شد.');
    }

    public function destroy(int $id)
    {
        $row = DB::table('acc_accounts')->where('id', $id)->first();
        abort_unless($row, 404);
        $used = Schema::hasTable('acc_journal_lines') && DB::table('acc_journal_lines')->where('account_id', $id)->exists();
        if (! empty($row->is_system) || $used) {
            DB::table('acc_accounts')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);

            return back()->with('success', 'حساب سیستمی/دارای گردش حذف نمی‌شود — غیرفعال شد.');
        }
        $kids = DB::table('acc_accounts')->where('parent_id', $id)->count();
        if ($kids > 0) {
            return back()->with('error', 'ابتدا زیرحساب‌ها را حذف یا جابه‌جا کنید.');
        }
        DB::table('acc_accounts')->where('id', $id)->delete();

        return back()->with('success', 'حساب حذف شد.');
    }

    public function saveMap(Request $request)
    {
        foreach (AccChart::defaultMap() as $key => $fallback) {
            $v = trim((string) $request->input('map_'.$key, ''));
            if ($v !== '') {
                AccChart::setSetting($key, $v);
            }
        }

        return back()->with('success', 'حساب‌های پیش‌فرض سند زدن ذخیره شد.');
    }

    public function trial(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $rows = [];
        try {
            $rows = AccJournal::trialBalance($from, $to);
        } catch (\Throwable) {
        }
        $debit = 0;
        $credit = 0;
        foreach ($rows as $r) {
            $debit += (int) ($r->debit ?? 0);
            $credit += (int) ($r->credit ?? 0);
        }

        return AccSafe::page('accounting::admin.reports.ledger', [
            'kind' => 'trial',
            'title' => 'تراز آزمایشی',
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'debit' => $debit,
            'credit' => $credit,
        ], 'تراز آزمایشی');
    }

    public function income(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $rows = [];
        try {
            $rows = array_values(array_filter(AccJournal::trialBalance($from, $to), function ($r) {
                return in_array((string) ($r->type ?? ''), ['income', 'cogs', 'expense'], true);
            }));
        } catch (\Throwable) {
        }
        $income = 0;
        $out = 0;
        foreach ($rows as $r) {
            $net = (int) $r->credit - (int) $r->debit;
            if (($r->type ?? '') === 'income') {
                $income += $net;
            } else {
                $out += ((int) $r->debit - (int) $r->credit);
            }
        }

        return AccSafe::page('accounting::admin.reports.ledger', [
            'kind' => 'income',
            'title' => 'سود و زیان',
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'debit' => $out,
            'credit' => $income,
            'profit' => $income - $out,
        ], 'سود و زیان');
    }

    public function balanceSheet(Request $request)
    {
        $to = $request->query('to', now()->toDateString());
        $rows = [];
        try {
            $rows = array_values(array_filter(AccJournal::trialBalance(null, $to), function ($r) {
                return in_array((string) ($r->type ?? ''), ['asset', 'liability', 'equity'], true);
            }));
        } catch (\Throwable) {
        }

        return AccSafe::page('accounting::admin.reports.ledger', [
            'kind' => 'balance',
            'title' => 'ترازنامه',
            'from' => null,
            'to' => $to,
            'rows' => $rows,
            'debit' => 0,
            'credit' => 0,
        ], 'ترازنامه');
    }
}
