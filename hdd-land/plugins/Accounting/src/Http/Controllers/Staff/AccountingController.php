<?php

namespace Plugins\Accounting\src\Http\Controllers\Staff;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;
use Plugins\Accounting\src\Support\AccSafe;

class AccountingController extends Controller
{
    public function __construct()
    {
        try {
            Plugin::ensureSchema();
            Plugin::seedDefaults();
        } catch (\Throwable) {
        }
    }

    public function hub()
    {
        return AccSafe::wrap('accounting::staff.hub', function () {
            $stats = [
                'sales_total' => 0, 'purchase_total' => 0, 'expense_total' => 0, 'proforma_open' => 0,
                'warehouses' => 0, 'banks' => 0, 'docs' => [], 'check_alerts' => 0,
            ];
            try {
                $stats = AccEngine::dashboardStats() + $stats;
            } catch (\Throwable) {
            }
            $recent = collect();
            if (Schema::hasTable('acc_documents')) {
                $recent = DB::table('acc_documents')->orderByDesc('id')->limit(10)->get();
            }

            return [
                'stats' => $stats,
                'types' => AccEngine::TYPES,
                'recent' => $recent,
                'portal' => 'staff',
            ];
        }, 'حسابداری کارمند');
    }

    public function docs(Request $request)
    {
        return AccSafe::wrap('accounting::staff.docs', function () use ($request) {
            $type = (string) $request->query('type', '');
            $docs = collect();
            if (Schema::hasTable('acc_documents')) {
                $q = DB::table('acc_documents')->orderByDesc('id');
                if ($type !== '' && isset(AccEngine::TYPES[$type])) {
                    $q->where('type', $type);
                }
                $docs = $q->limit(80)->get();
            }

            return [
                'docs' => $docs,
                'type' => $type,
                'types' => AccEngine::TYPES,
                'portal' => 'staff',
            ];
        }, 'اسناد کارمند');
    }

    public function show(int $id)
    {
        return AccSafe::wrap('accounting::staff.doc-show', function () use ($id) {
            $doc = Schema::hasTable('acc_documents')
                ? DB::table('acc_documents')->where('id', $id)->first()
                : null;
            $lines = Schema::hasTable('acc_document_lines')
                ? DB::table('acc_document_lines')->where('document_id', $id)->get()
                : collect();
            $serials = Schema::hasTable('acc_document_serials')
                ? DB::table('acc_document_serials')->where('document_id', $id)->get()
                : collect();

            return [
                'doc' => $doc,
                'lines' => $lines,
                'serials' => $serials,
                'types' => AccEngine::TYPES,
                'portal' => 'staff',
            ];
        }, 'سند');
    }

    public function stock()
    {
        return AccSafe::wrap('accounting::staff.stock', function () {
            $balances = collect();
            if (Schema::hasTable('acc_stock_balances')) {
                $balances = DB::table('acc_stock_balances as b')
                    ->leftJoin('acc_warehouses as w', 'w.id', '=', 'b.warehouse_id')
                    ->select('b.*', 'w.name as warehouse_name')
                    ->orderBy('w.name')->limit(150)->get();
            }

            return [
                'balances' => $balances,
                'portal' => 'staff',
            ];
        }, 'انبار کارمند');
    }

    public function reports()
    {
        return AccSafe::wrap('accounting::staff.reports', function () {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->toDateString();
            $sum = function (string $type) use ($from, $to): int {
                try {
                    if (! Schema::hasTable('acc_documents')) {
                        return 0;
                    }

                    return (int) DB::table('acc_documents')->where('type', $type)->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total');
                } catch (\Throwable) {
                    return 0;
                }
            };

            return [
                'sales' => $sum('sale'),
                'purchase' => $sum('purchase'),
                'expense' => $sum('expense'),
                'from' => $from,
                'to' => $to,
                'portal' => 'staff',
            ];
        }, 'گزارش کارمند');
    }

    /**
     * هدایت کارمند به منوی حسابداری ادمین (وب موبایل) با کنترل ACL.
     */
    public function toAdmin(Request $request, string $target = '')
    {
        $route = $request->route();
        if ($target === '' && $route) {
            $target = (string) ($route->parameter('target') ?? ($route->defaults['target'] ?? ''));
        }
        $map = [
            '' => ['perm' => 'accounting', 'path' => '/admin/accounting'],
            'checks' => ['perm' => 'accounting.checks', 'path' => '/admin/accounting/checks'],
            'installments' => ['perm' => 'accounting.installments', 'path' => '/admin/accounting/installments'],
            'settings' => ['perm' => 'accounting.settings', 'path' => '/admin/accounting/settings'],
            'reports' => ['perm' => 'accounting.reports', 'path' => '/admin/accounting/reports'],
        ];
        $cfg = $map[$target] ?? $map[''];
        $user = $request->user();
        $ok = $user && method_exists($user, 'hasStaffPermission') && (
            $user->hasStaffPermission($cfg['perm'])
            || ($cfg['perm'] !== 'accounting.settings' && $user->hasStaffPermission('accounting'))
        );
        if (! $ok) {
            return redirect()->to(url('/staff'))->with('error', 'دسترسی حسابداری برای این بخش فعال نیست.');
        }

        return redirect()->to(url($cfg['path']));
    }
}
