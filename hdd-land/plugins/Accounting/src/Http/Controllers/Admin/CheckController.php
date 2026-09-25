<?php

namespace Plugins\Accounting\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;
use Plugins\Accounting\src\Support\AccSafe;

class CheckController extends Controller
{
    public function __construct()
    {
        try {
            Plugin::ensureSchema();
        } catch (\Throwable) {
        }
    }

    public function index(Request $request, string $heading = 'مدیریت چک‌ها', string $forceDirection = '')
    {
        return AccSafe::wrap('accounting::admin.checks', function () use ($request, $heading, $forceDirection) {
            $status = trim((string) $request->get('status', ''));
            $direction = $forceDirection !== '' ? $forceDirection : trim((string) $request->get('direction', ''));
            $q = trim((string) $request->get('q', ''));
            $items = collect();
            $summary = collect();
            $alerts = [];
            if (Schema::hasTable('acc_checks')) {
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
                $alerts = AccEngine::dueCheckAlerts(40);
            }

            return [
                'items' => $items,
                'banks' => Schema::hasTable('acc_banks') ? DB::table('acc_banks')->where('is_active', true)->orderBy('name')->get() : collect(),
                'books' => Schema::hasTable('acc_checkbooks') ? DB::table('acc_checkbooks')->where('is_active', 1)->orderBy('owner_name')->get() : collect(),
                'status' => $status,
                'direction' => $direction,
                'q' => $q,
                'summary' => $summary,
                'statuses' => AccEngine::CHECK_STATUSES,
                'directions' => AccEngine::CHECK_DIRECTIONS,
                'alerts' => $alerts,
                'heading' => $heading,
                'forceDirection' => $forceDirection,
            ];
        }, $heading);
    }

    public function received(Request $request)
    {
        $request->merge(['direction' => 'receivable']);

        return $this->index($request, 'چک دریافتی از مشتری', 'receivable');
    }

    public function spent(Request $request)
    {
        $request->merge(['direction' => 'spent']);

        return $this->index($request, 'چک خرج‌شده به مشتری', 'spent');
    }

    public function alerts()
    {
        return AccSafe::wrap('accounting::admin.check-alerts', function () {
            $items = [];
            $parties = collect();
            try {
                $items = AccEngine::dueCheckAlerts(300);
            } catch (\Throwable) {
            }
            if (Schema::hasTable('acc_party_alerts')) {
                $parties = DB::table('acc_party_alerts')->orderBy('party_name')->get();
            }

            return [
                'items' => $items,
                'defaultDays' => AccEngine::defaultCheckAlertDays(),
                'parties' => $parties,
                'statuses' => AccEngine::CHECK_STATUSES,
            ];
        }, 'اخطار سررسید چک');
    }

    public function saveAlertSetting(Request $request)
    {
        $days = max(1, min(90, (int) $request->input('check_alert_days', 3)));
        if (Schema::hasTable('acc_settings')) {
            $exists = DB::table('acc_settings')->where('k', 'check_alert_days')->exists();
            if ($exists) {
                DB::table('acc_settings')->where('k', 'check_alert_days')->update(['v' => (string) $days, 'updated_at' => now()]);
            } else {
                DB::table('acc_settings')->insert(['k' => 'check_alert_days', 'v' => (string) $days, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $partyName = trim((string) $request->input('party_name', ''));
        $partyDays = max(1, min(90, (int) $request->input('party_alert_days', $days)));
        if ($partyName !== '' && Schema::hasTable('acc_party_alerts')) {
            $uid = $request->filled('party_user_id') ? (int) $request->input('party_user_id') : null;
            $q = DB::table('acc_party_alerts')->where('party_name', $partyName);
            if ($uid) {
                $q->orWhere('party_user_id', $uid);
            }
            $row = $q->first();
            $payload = [
                'party_name' => $partyName,
                'party_user_id' => $uid,
                'owner_type' => (string) $request->input('owner_type', 'person'),
                'alert_days' => $partyDays,
                'updated_at' => now(),
            ];
            if ($row) {
                DB::table('acc_party_alerts')->where('id', $row->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('acc_party_alerts')->insert($payload);
            }
        }

        return back()->with('success', 'اخطار سررسید ذخیره شد.');
    }

    public function books()
    {
        $items = Schema::hasTable('acc_checkbooks')
            ? DB::table('acc_checkbooks')->orderByDesc('id')->get()
            : collect();

        return AccSafe::page('accounting::admin.checkbooks', [
            'items' => $items,
            'banks' => Schema::hasTable('acc_banks') ? DB::table('acc_banks')->where('is_active', 1)->orderBy('name')->get() : collect(),
            'defaultDays' => AccEngine::defaultCheckAlertDays(),
        ], 'دسته چک');
    }

    public function storeBook(Request $request)
    {
        if (! Schema::hasTable('acc_checkbooks')) {
            return back()->with('error', 'جدول دسته چک آماده نیست.');
        }
        $name = trim((string) $request->input('owner_name', ''));
        $count = (int) $request->input('leaf_count', 0);
        if ($name === '' || $count < 1) {
            return back()->with('error', 'نام صاحب دسته و تعداد برگ الزامی است.');
        }
        $owner = (string) $request->input('owner_type', 'company');
        if (! in_array($owner, ['company', 'person'], true)) {
            $owner = 'company';
        }
        DB::table('acc_checkbooks')->insert([
            'owner_type' => $owner,
            'owner_name' => $name,
            'bank_name' => trim((string) $request->input('bank_name', '')) ?: null,
            'bank_id' => $request->filled('bank_id') ? (int) $request->input('bank_id') : null,
            'series_from' => trim((string) $request->input('series_from', '')) ?: null,
            'series_to' => trim((string) $request->input('series_to', '')) ?: null,
            'leaf_count' => $count,
            'used_count' => (int) $request->input('used_count', 0),
            'alert_days' => max(1, min(90, (int) $request->input('alert_days', AccEngine::defaultCheckAlertDays()))),
            'is_active' => true,
            'notes' => trim((string) $request->input('notes', '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'دسته چک ثبت شد.');
    }

    public function updateBook(Request $request, int $id)
    {
        $row = DB::table('acc_checkbooks')->where('id', $id)->first();
        abort_unless($row, 404);
        DB::table('acc_checkbooks')->where('id', $id)->update([
            'owner_type' => in_array($request->input('owner_type'), ['company', 'person'], true) ? $request->input('owner_type') : $row->owner_type,
            'owner_name' => trim((string) $request->input('owner_name', $row->owner_name)) ?: $row->owner_name,
            'bank_name' => trim((string) $request->input('bank_name', '')) ?: null,
            'leaf_count' => max(1, (int) $request->input('leaf_count', $row->leaf_count)),
            'used_count' => max(0, (int) $request->input('used_count', $row->used_count)),
            'alert_days' => max(1, min(90, (int) $request->input('alert_days', $row->alert_days))),
            'is_active' => $request->boolean('is_active', (bool) $row->is_active),
            'notes' => trim((string) $request->input('notes', '')) ?: null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'دسته چک به‌روز شد.');
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('acc_checks')) {
            return back()->with('error', 'جدول چک‌ها آماده نیست.');
        }

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
        $bookId = $request->filled('checkbook_id') ? (int) $request->input('checkbook_id') : null;
        $partyName = trim((string) $request->input('party_name', '')) ?: null;
        $partyUserId = $request->filled('party_user_id') ? (int) $request->input('party_user_id') : null;
        $alertDays = AccEngine::alertDaysForParty($partyUserId, $partyName, $bookId);
        if ($request->filled('alert_days')) {
            $alertDays = max(1, min(90, (int) $request->input('alert_days')));
        }
        $row = [
            'number' => $number,
            'direction' => $direction,
            'status' => (string) $request->input('status', $direction === 'spent' ? 'spent' : 'pending'),
            'bank_name' => trim((string) $request->input('bank_name', '')) ?: null,
            'branch' => trim((string) $request->input('branch', '')) ?: null,
            'account_no' => trim((string) $request->input('account_no', '')) ?: null,
            'sayad' => trim((string) $request->input('sayad', '')) ?: null,
            'party_name' => $partyName,
            'party_user_id' => $partyUserId,
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
        ];
        if (Schema::hasColumn('acc_checks', 'checkbook_id')) {
            $row['checkbook_id'] = $bookId;
        }
        if (Schema::hasColumn('acc_checks', 'endorsed_to')) {
            $row['endorsed_to'] = trim((string) $request->input('endorsed_to', '')) ?: ($direction === 'spent' ? $partyName : null);
        }
        if (Schema::hasColumn('acc_checks', 'alert_days')) {
            $row['alert_days'] = $alertDays;
        }
        DB::table('acc_checks')->insert($row);
        if ($bookId && Schema::hasTable('acc_checkbooks')) {
            try {
                DB::table('acc_checkbooks')->where('id', $bookId)->increment('used_count');
            } catch (\Throwable) {
            }
        }

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
        try {
            \Plugins\Accounting\src\Support\AccJournal::postCheckStatus($row, (string) $row->status, $status);
        } catch (\Throwable) {
        }

        return back()->with('success', 'وضعیت چک: ' . AccEngine::CHECK_STATUSES[$status]);
    }

    public function destroy(int $id)
    {
        DB::table('acc_checks')->where('id', $id)->delete();

        return back()->with('success', 'چک حذف شد.');
    }
}
