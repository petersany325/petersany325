<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;

class InstallmentController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
    }

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('acc_installment_requests'), 404);

        $status = trim((string) $request->get('status', ''));
        $q = trim((string) $request->get('q', ''));
        $query = DB::table('acc_installment_requests as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->select(['r.*', 'u.name as user_name', 'u.email as user_email', 'u.mobile as user_mobile'])
            ->orderByDesc('r.id');
        if ($status !== '' && isset(AccEngine::INSTALLMENT_STATUSES[$status])) {
            $query->where('r.status', $status);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('r.number', 'like', "%{$q}%")
                    ->orWhere('r.customer_name', 'like', "%{$q}%")
                    ->orWhere('r.customer_mobile', 'like', "%{$q}%")
                    ->orWhere('r.product_title', 'like', "%{$q}%");
            });
        }

        return view('accounting::admin.installments', [
            'items' => $query->limit(300)->get(),
            'status' => $status,
            'q' => $q,
            'statuses' => AccEngine::INSTALLMENT_STATUSES,
            'pending' => (int) DB::table('acc_installment_requests')->where('status', 'pending')->count(),
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('acc_installment_requests')->where('id', $id)->first();
        abort_unless($row, 404);
        $schedules = Schema::hasTable('acc_installment_schedules')
            ? DB::table('acc_installment_schedules')->where('request_id', $id)->orderBy('installment_no')->get()
            : collect();
        $user = $row->user_id ? DB::table('users')->where('id', $row->user_id)->first() : null;

        return view('accounting::admin.installment-show', [
            'row' => $row,
            'schedules' => $schedules,
            'user' => $user,
            'statuses' => AccEngine::INSTALLMENT_STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('acc_installment_requests'), 404);

        $price = (int) preg_replace('/\D+/', '', (string) $request->input('product_price', 0));
        $down = (int) preg_replace('/\D+/', '', (string) $request->input('down_payment', 0));
        $months = max(1, min(36, (int) $request->input('months', 3)));
        $remain = max(0, $price - $down);
        $monthly = $months > 0 ? (int) ceil($remain / $months) : 0;
        $name = trim((string) $request->input('customer_name', ''));
        $title = trim((string) $request->input('product_title', ''));
        if ($name === '' || $title === '' || $price <= 0) {
            return back()->with('error', 'نام مشتری، عنوان کالا و قیمت الزامی است.');
        }

        $id = (int) DB::table('acc_installment_requests')->insertGetId([
            'number' => AccEngine::nextInstallmentNumber(),
            'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : null,
            'customer_name' => $name,
            'customer_mobile' => trim((string) $request->input('customer_mobile', '')) ?: null,
            'customer_national_id' => trim((string) $request->input('customer_national_id', '')) ?: null,
            'product_title' => $title,
            'product_id' => $request->filled('product_id') ? (int) $request->input('product_id') : null,
            'product_price' => $price,
            'down_payment' => $down,
            'months' => $months,
            'monthly_amount' => $monthly,
            'total_amount' => $down + ($monthly * $months),
            'status' => 'pending',
            'ticket_id' => null,
            'approved_by' => null,
            'customer_note' => trim((string) $request->input('customer_note', '')) ?: null,
            'admin_note' => trim((string) $request->input('admin_note', '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.accounting.installments.show', $id)
            ->with('success', 'درخواست اقساط ثبت شد.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $row = DB::table('acc_installment_requests')->where('id', $id)->first();
        abort_unless($row, 404);
        $status = (string) $request->input('status', '');
        abort_unless(isset(AccEngine::INSTALLMENT_STATUSES[$status]), 422);

        $data = [
            'status' => $status,
            'admin_note' => trim((string) $request->input('admin_note', $row->admin_note ?? '')) ?: null,
            'updated_at' => now(),
        ];

        if (in_array($status, ['approved', 'active'], true)) {
            $data['approved_by'] = Auth::id();
            $months = max(1, (int) $row->months);
            $monthly = (int) $row->monthly_amount;
            if ($request->filled('monthly_amount')) {
                $monthly = (int) preg_replace('/\D+/', '', (string) $request->input('monthly_amount'));
                $data['monthly_amount'] = $monthly;
                $data['total_amount'] = (int) $row->down_payment + ($monthly * $months);
            }
            AccEngine::buildInstallmentSchedule($id, $months, $monthly);
            if ($status === 'approved') {
                // keep approved until staff activates
            }
        }

        DB::table('acc_installment_requests')->where('id', $id)->update($data);

        return back()->with('success', 'وضعیت: ' . AccEngine::INSTALLMENT_STATUSES[$status]);
    }

    public function markSchedule(Request $request, int $id, int $scheduleId)
    {
        $sched = DB::table('acc_installment_schedules')->where('id', $scheduleId)->where('request_id', $id)->first();
        abort_unless($sched, 404);
        $status = (string) $request->input('status', 'paid');
        if (! in_array($status, ['pending', 'paid', 'overdue', 'waived'], true)) {
            $status = 'paid';
        }
        $paid = (int) preg_replace('/\D+/', '', (string) $request->input('paid_amount', $sched->amount));
        DB::table('acc_installment_schedules')->where('id', $scheduleId)->update([
            'status' => $status,
            'paid_amount' => $status === 'paid' ? $paid : 0,
            'paid_at' => $status === 'paid' ? now()->toDateString() : null,
            'notes' => trim((string) $request->input('notes', '')) ?: $sched->notes,
            'updated_at' => now(),
        ]);

        $pending = (int) DB::table('acc_installment_schedules')->where('request_id', $id)->where('status', '!=', 'paid')->where('status', '!=', 'waived')->count();
        if ($pending === 0) {
            DB::table('acc_installment_requests')->where('id', $id)->update(['status' => 'completed', 'updated_at' => now()]);
        }

        return back()->with('success', 'قسط به‌روز شد.');
    }

    public function destroy(int $id)
    {
        if (Schema::hasTable('acc_installment_schedules')) {
            DB::table('acc_installment_schedules')->where('request_id', $id)->delete();
        }
        DB::table('acc_installment_requests')->where('id', $id)->delete();

        return redirect()->route('admin.accounting.installments')->with('success', 'درخواست حذف شد.');
    }
}
