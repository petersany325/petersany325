<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;
use Plugins\Accounting\src\Support\AccSafe;

class StaffController extends Controller
{
    public function __construct()
    {
        try {
            Plugin::ensureSchema();
        } catch (\Throwable) {
        }
    }

    public function index()
    {
        $rows = collect();
        try {
            if (Schema::hasTable('staff_members')) {
                $rows = DB::table('staff_members')->orderByDesc('is_active')->orderBy('name')->get();
            }
        } catch (\Throwable) {
        }

        return AccSafe::page('accounting::admin.staff', [
            'rows' => $rows,
            'kinds' => AccEngine::STAFF_KINDS,
            'defaultAlert' => AccEngine::defaultCheckAlertDays(),
        ], 'کارمند و ویزیتور');
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('staff_members')) {
            return back()->with('error', 'جدول کارمندان آماده نیست.');
        }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return back()->with('error', 'نام کارمند / ویزیتور الزامی است.');
        }
        $kind = (string) $request->input('kind', 'employee');
        if (! isset(AccEngine::STAFF_KINDS[$kind])) {
            $kind = 'employee';
        }
        $row = [
            'name' => $name,
            'phone' => trim((string) $request->input('phone', '')) ?: null,
            'role' => trim((string) $request->input('role', '')) ?: ($kind === 'visitor' ? 'visitor' : 'seller'),
            'base_salary' => (int) str_replace(',', '', (string) $request->input('base_salary', 0)),
            'commission_rate' => max(0, min(100, (float) $request->input('commission_rate', 0))),
            'is_active' => $request->boolean('is_active', true),
            'notes' => trim((string) $request->input('notes', '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('staff_members', 'kind')) {
            $row['kind'] = $kind;
        }
        if (Schema::hasColumn('staff_members', 'profit_rate')) {
            $row['profit_rate'] = max(0, min(100, (float) $request->input('profit_rate', 0)));
        }
        if (Schema::hasColumn('staff_members', 'check_alert_days')) {
            $row['check_alert_days'] = max(1, min(90, (int) $request->input('check_alert_days', AccEngine::defaultCheckAlertDays())));
        }
        if (Schema::hasColumn('staff_members', 'email')) {
            $row['email'] = trim((string) $request->input('email', '')) ?: null;
        }
        if ($request->filled('user_id') && Schema::hasColumn('staff_members', 'user_id')) {
            $row['user_id'] = (int) $request->input('user_id');
        }
        DB::table('staff_members')->insert($row);

        return back()->with('success', 'پرونده کارمند / ویزیتور ثبت شد. کمیسیون و درصد سود از همین‌جا خوانده می‌شود.');
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('staff_members')->where('id', $id)->first();
        abort_unless($row, 404);
        $kind = (string) $request->input('kind', $row->kind ?? 'employee');
        if (! isset(AccEngine::STAFF_KINDS[$kind])) {
            $kind = 'employee';
        }
        $payload = [
            'name' => trim((string) $request->input('name', $row->name)) ?: $row->name,
            'phone' => trim((string) $request->input('phone', '')) ?: null,
            'role' => trim((string) $request->input('role', '')) ?: ($row->role ?? 'seller'),
            'base_salary' => (int) str_replace(',', '', (string) $request->input('base_salary', $row->base_salary ?? 0)),
            'commission_rate' => max(0, min(100, (float) $request->input('commission_rate', $row->commission_rate ?? 0))),
            'is_active' => $request->boolean('is_active', (bool) ($row->is_active ?? true)),
            'notes' => trim((string) $request->input('notes', '')) ?: null,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('staff_members', 'kind')) {
            $payload['kind'] = $kind;
        }
        if (Schema::hasColumn('staff_members', 'profit_rate')) {
            $payload['profit_rate'] = max(0, min(100, (float) $request->input('profit_rate', $row->profit_rate ?? 0)));
        }
        if (Schema::hasColumn('staff_members', 'check_alert_days')) {
            $payload['check_alert_days'] = max(1, min(90, (int) $request->input('check_alert_days', $row->check_alert_days ?? 3)));
        }
        DB::table('staff_members')->where('id', $id)->update($payload);

        return back()->with('success', 'پرونده به‌روز شد.');
    }

    public function destroy(int $id)
    {
        if (! Schema::hasTable('staff_members')) {
            return back();
        }
        DB::table('staff_members')->where('id', $id)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'کارمند غیرفعال شد.');
    }
}
