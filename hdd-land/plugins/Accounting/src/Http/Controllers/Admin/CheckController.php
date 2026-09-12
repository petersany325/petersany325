<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;

class CheckController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
    }

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('acc_checks'), 404);

        $status = trim((string) $request->get('status', ''));
        $direction = trim((string) $request->get('direction', ''));
        $q = trim((string) $request->get('q', ''));

        $query = DB::table('acc_checks')->orderByDesc('id');
        if ($status !== '' && isset(AccEngine::CHECK_STATUSES[$status])) {
            $query->where('status', $status);
        }
        if ($direction !== '' && isset(AccEngine::CHECK_DIRECTIONS[$direction])) {
            $query->where('direction', $direction);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('number', 'like', "%{$q}%")
                    ->orWhere('sayad', 'like', "%{$q}%")
                    ->orWhere('party_name', 'like', "%{$q}%")
                    ->orWhere('bank_name', 'like', "%{$q}%");
            });
        }

        $items = $query->limit(300)->get();
        $summary = DB::table('acc_checks')
            ->selectRaw('status, COUNT(*) as c, SUM(amount) as s')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('accounting::admin.checks', [
            'items' => $items,
            'banks' => DB::table('acc_banks')->where('is_active', true)->orderBy('name')->get(),
            'status' => $status,
            'direction' => $direction,
            'q' => $q,
            'summary' => $summary,
            'statuses' => AccEngine::CHECK_STATUSES,
            'directions' => AccEngine::CHECK_DIRECTIONS,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('acc_checks'), 404);

        $direction = (string) $request->input('direction', 'receivable');
        if (! isset(AccEngine::CHECK_DIRECTIONS[$direction])) {
            $direction = 'receivable';
        }
        $amount = (int) preg_replace('/\D+/', '', (string) $request->input('amount', 0));
        $number = trim((string) $request->input('number', ''));
        if ($number === '') {
            $number = AccEngine::nextCheckNumber();
        }
        if ($amount <= 0) {
            return back()->with('error', 'مبلغ چک معتبر نیست.');
        }

        DB::table('acc_checks')->insert([
            'number' => $number,
            'direction' => $direction,
            'status' => (string) $request->input('status', 'pending'),
            'bank_name' => trim((string) $request->input('bank_name', '')) ?: null,
            'branch' => trim((string) $request->input('branch', '')) ?: null,
            'account_no' => trim((string) $request->input('account_no', '')) ?: null,
            'sayad' => trim((string) $request->input('sayad', '')) ?: null,
            'party_name' => trim((string) $request->input('party_name', '')) ?: null,
            'party_user_id' => $request->filled('party_user_id') ? (int) $request->input('party_user_id') : null,
            'bank_id' => $request->filled('bank_id') ? (int) $request->input('bank_id') : null,
            'document_id' => $request->filled('document_id') ? (int) $request->input('document_id') : null,
            'staff_id' => $request->filled('staff_id') ? (int) $request->input('staff_id') : null,
            'amount' => $amount,
            'issue_date' => $request->input('issue_date') ?: now()->toDateString(),
            'due_date' => $request->input('due_date') ?: null,
            'clear_date' => null,
            'notes' => trim((string) $request->input('notes', '')) ?: null,
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'چک ثبت شد.');
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('acc_checks')->where('id', $id)->first();
        abort_unless($row, 404);

        $direction = (string) $request->input('direction', $row->direction);
        if (! isset(AccEngine::CHECK_DIRECTIONS[$direction])) {
            $direction = $row->direction;
        }
        $status = (string) $request->input('status', $row->status);
        if (! isset(AccEngine::CHECK_STATUSES[$status])) {
            $status = $row->status;
        }
        $amount = (int) preg_replace('/\D+/', '', (string) $request->input('amount', $row->amount));

        $clearDate = $row->clear_date;
        if (in_array($status, ['received', 'paid'], true) && ! $clearDate) {
            $clearDate = now()->toDateString();
        }
        if (in_array($status, ['pending', 'returned', 'bounced', 'cancelled'], true)) {
            $clearDate = $request->input('clear_date') ?: null;
        }

        DB::table('acc_checks')->where('id', $id)->update([
            'number' => trim((string) $request->input('number', $row->number)) ?: $row->number,
            'direction' => $direction,
            'status' => $status,
            'bank_name' => trim((string) $request->input('bank_name', '')) ?: null,
            'branch' => trim((string) $request->input('branch', '')) ?: null,
            'account_no' => trim((string) $request->input('account_no', '')) ?: null,
            'sayad' => trim((string) $request->input('sayad', '')) ?: null,
            'party_name' => trim((string) $request->input('party_name', '')) ?: null,
            'party_user_id' => $request->filled('party_user_id') ? (int) $request->input('party_user_id') : null,
            'bank_id' => $request->filled('bank_id') ? (int) $request->input('bank_id') : null,
            'amount' => max(0, $amount),
            'issue_date' => $request->input('issue_date') ?: $row->issue_date,
            'due_date' => $request->input('due_date') ?: null,
            'clear_date' => $clearDate,
            'notes' => trim((string) $request->input('notes', '')) ?: null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'چک به‌روز شد.');
    }

    public function setStatus(Request $request, int $id)
    {
        $row = DB::table('acc_checks')->where('id', $id)->first();
        abort_unless($row, 404);
        $status = (string) $request->input('status', '');
        abort_unless(isset(AccEngine::CHECK_STATUSES[$status]), 422);

        $data = ['status' => $status, 'updated_at' => now()];
        if (in_array($status, ['received', 'paid', 'delivered'], true)) {
            $data['clear_date'] = now()->toDateString();
        }
        if (in_array($status, ['returned', 'bounced', 'cancelled', 'pending'], true)) {
            $data['clear_date'] = null;
        }
        DB::table('acc_checks')->where('id', $id)->update($data);

        return back()->with('success', 'وضعیت چک: ' . AccEngine::CHECK_STATUSES[$status]);
    }

    public function destroy(int $id)
    {
        DB::table('acc_checks')->where('id', $id)->delete();

        return back()->with('success', 'چک حذف شد.');
    }
}
