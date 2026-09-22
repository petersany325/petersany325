<?php

namespace App\Http\Controllers;

use App\Models\DailyLogCategory;
use App\Models\DailyLogEntry;
use App\Models\Reception;
use App\Models\User;
use App\Support\DailyLogSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canManage = $user->canAccess('daily_logs.manage');
        $date = $this->resolveDate($request->input('date'));
        $employeeId = (int) $request->input('user_id', $user->id);

        if (! $canManage) {
            $employeeId = (int) $user->id;
        }

        $employee = User::query()->findOrFail($employeeId);

        $entries = DailyLogEntry::query()
            ->with(['category', 'creator', 'reception.customer'])
            ->where('user_id', $employee->id)
            ->whereDate('work_date', $date->toDateString())
            ->orderByDesc('id')
            ->get();

        $categories = DailyLogCategory::query()->active()->ordered()->get();
        $employees = $canManage
            ? User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role'])
            : collect([$user]);

        $summary = [
            'count' => $entries->count(),
            'quantity' => (int) $entries->sum(fn ($e) => (int) ($e->quantity ?? 0)),
            'minutes' => (int) $entries->sum(fn ($e) => (int) ($e->minutes ?? 0)),
            'with_ticket' => $entries->whereNotNull('reception_id')->count(),
        ];

        return view('daily-logs.index', [
            'date' => $date,
            'employee' => $employee,
            'entries' => $entries,
            'categories' => $categories,
            'employees' => $employees,
            'canManage' => $canManage,
            'summary' => $summary,
            'ticketSearchUrl' => \Illuminate\Support\Facades\Route::has('daily-logs.tickets')
                ? route('daily-logs.tickets')
                : url('/daily-logs/tickets'),
            'workHints' => [
                'تعویض قطعه',
                'تشخیص ایراد',
                'تست نهایی',
                'نصب قطعه',
                'تمیزکاری برد',
                'هماهنگی با مشتری',
            ],
            'settings' => [
                'require_note' => DailyLogSettings::requireNote(),
                'show_quantity' => DailyLogSettings::showQuantity(),
                'allow_past_days' => DailyLogSettings::allowPastDays(),
                'editable' => $this->dateIsEditable($date, $canManage),
            ],
        ]);
    }

    /** JSON: جستجوی قبض برای ثبت در دفتر روز (کارمند / کارآموز). */
    public function searchTickets(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (function_exists('normalize_receipt_search_query')) {
            $q = normalize_receipt_search_query($q);
        }
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'count' => 0, 'tickets' => []]);
        }

        $digits = preg_replace('/\D+/', '', $q) ?? '';
        $qUpper = mb_strtoupper($q);

        $rows = Reception::query()
            ->with(['customer:id,name,phone'])
            ->where(function ($inner) use ($q, $qUpper, $digits) {
                $inner->where('ticket_no', 'like', $q.'%')
                    ->orWhere('receipt_no', 'like', $q.'%')
                    ->orWhere('serial_number', 'like', $qUpper.'%')
                    ->orWhere('product_name', 'like', '%'.$q.'%')
                    ->orWhere('model', 'like', '%'.$q.'%')
                    ->orWhereHas('customer', function ($c) use ($q, $digits) {
                        $c->where('name', 'like', '%'.$q.'%');
                        if (strlen($digits) >= 3) {
                            $c->orWhere('phone', 'like', '%'.$digits.'%');
                        }
                    });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'ticket_no', 'receipt_no', 'serial_number', 'product_name', 'brand', 'model', 'status', 'customer_id']);

        $statusLabels = Reception::availableStatuses();

        return response()->json([
            'ok' => true,
            'count' => $rows->count(),
            'tickets' => $rows->map(fn (Reception $r) => [
                'id' => $r->id,
                'ticket_no' => $r->ticket_no,
                'receipt_no' => $r->receipt_no,
                'serial' => $r->serial_number,
                'product' => trim(($r->brand ? $r->brand.' ' : '').($r->model ?: $r->product_name ?: '')),
                'customer' => $r->customer?->name,
                'phone' => $r->customer?->phone,
                'status' => $r->status,
                'status_label' => $statusLabels[$r->status] ?? $r->status,
                'label' => ($r->ticket_no ?: $r->receipt_no)
                    .' — '.($r->customer?->name ?: 'بدون مشتری')
                    .' — '.($r->serial_number ?: 'بدون سریال'),
            ])->values(),
        ]);
    }

    public function report(Request $request)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        $from = $this->resolveDate($request->input('from'), now('Asia/Tehran')->subDays(6));
        $to = $this->resolveDate($request->input('to'));
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $employeeId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $role = (string) $request->input('role', '');
        $onlyTickets = $request->boolean('only_tickets');

        $base = DailyLogEntry::query()
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->when($employeeId, fn ($q) => $q->where('user_id', $employeeId))
            ->when($categoryId, fn ($q) => $q->where('daily_log_category_id', $categoryId))
            ->when($onlyTickets, fn ($q) => $q->whereNotNull('reception_id'))
            ->when($role !== '', function ($q) use ($role) {
                $q->whereHas('user', fn ($u) => $u->where('role', $role));
            });

        $entries = (clone $base)
            ->with(['user', 'category', 'reception.customer'])
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        $byEmployee = (clone $base)
            ->selectRaw('user_id, COUNT(*) as cnt, COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(minutes),0) as mins, SUM(CASE WHEN reception_id IS NOT NULL THEN 1 ELSE 0 END) as tickets')
            ->groupBy('user_id')
            ->get();

        $usersById = User::query()
            ->whereIn('id', $byEmployee->pluck('user_id')->filter()->all())
            ->get(['id', 'name', 'role'])
            ->keyBy('id');

        foreach ($byEmployee as $row) {
            $row->setRelation('user', $usersById->get($row->user_id));
        }
        $byEmployee = $byEmployee->sortByDesc('cnt')->values();

        $byCategory = (clone $base)
            ->selectRaw("COALESCE(category_name, 'آزاد') as cat, COUNT(*) as cnt, COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(minutes),0) as mins")
            ->groupBy('cat')
            ->orderByDesc('cnt')
            ->get();

        $totals = [
            'count' => (int) (clone $base)->count(),
            'quantity' => (int) (clone $base)->sum('quantity'),
            'minutes' => (int) (clone $base)->sum('minutes'),
            'tickets' => (int) (clone $base)->whereNotNull('reception_id')->count(),
        ];

        return view('daily-logs.report', [
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'byEmployee' => $byEmployee,
            'byCategory' => $byCategory,
            'totals' => $totals,
            'employees' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'categories' => DailyLogCategory::query()->ordered()->get(['id', 'name']),
            'employeeId' => $employeeId,
            'categoryId' => $categoryId,
            'role' => $role,
            'onlyTickets' => $onlyTickets,
        ]);
    }

    public function store(Request $request)
    {
        $actor = auth()->user();
        $canManage = $actor->canAccess('daily_logs.manage');
        $date = $this->resolveDate($request->input('work_date'));
        $this->assertEditable($date, $canManage);

        $employeeId = (int) $request->input('user_id', $actor->id);
        if (! $canManage) {
            $employeeId = (int) $actor->id;
        }

        $data = $this->validatedEntry($request);
        $category = null;
        if (! empty($data['daily_log_category_id'])) {
            $category = DailyLogCategory::query()->active()->findOrFail($data['daily_log_category_id']);
        }

        $reception = $this->resolveReception($data['reception_id'] ?? null, $category);

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = $this->defaultTitle($category, $reception);
        }

        DailyLogEntry::create([
            'user_id' => $employeeId,
            'work_date' => $date->toDateString(),
            'daily_log_category_id' => $category?->id,
            'reception_id' => $reception?->id,
            'category_name' => $category?->name,
            'title' => $title,
            'body' => $data['body'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'minutes' => $data['minutes'] ?? null,
            'created_by' => $actor->id,
        ]);

        return redirect()
            ->route('daily-logs.index', ['date' => $date->toDateString(), 'user_id' => $employeeId])
            ->with('success', 'رویداد در دفتر روز ثبت شد.'.($reception ? ' (قبض '.$reception->ticket_no.')' : ''));
    }

    public function update(Request $request, DailyLogEntry $dailyLog)
    {
        $actor = auth()->user();
        $canManage = $actor->canAccess('daily_logs.manage');
        abort_unless($canManage || (int) $dailyLog->user_id === (int) $actor->id, 403);

        $date = $dailyLog->work_date instanceof Carbon
            ? $dailyLog->work_date->copy()->timezone('Asia/Tehran')
            : Carbon::parse($dailyLog->work_date, 'Asia/Tehran');
        $this->assertEditable($date, $canManage);

        $data = $this->validatedEntry($request);
        $category = null;
        if (! empty($data['daily_log_category_id'])) {
            $category = DailyLogCategory::query()->find($data['daily_log_category_id']);
        }

        $reception = $this->resolveReception($data['reception_id'] ?? null, $category, false);

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = $this->defaultTitle($category, $reception) ?: ($dailyLog->category_name ?: 'رویداد روزانه');
        }

        $dailyLog->update([
            'daily_log_category_id' => $category?->id,
            'reception_id' => $reception?->id ?? $dailyLog->reception_id,
            'category_name' => $category?->name ?? $dailyLog->category_name,
            'title' => $title,
            'body' => $data['body'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'minutes' => $data['minutes'] ?? null,
        ]);

        return back()->with('success', 'رویداد به‌روزرسانی شد.');
    }

    public function destroy(DailyLogEntry $dailyLog)
    {
        $actor = auth()->user();
        $canManage = $actor->canAccess('daily_logs.manage');
        abort_unless($canManage || (int) $dailyLog->user_id === (int) $actor->id, 403);

        $date = $dailyLog->work_date instanceof Carbon
            ? $dailyLog->work_date->copy()->timezone('Asia/Tehran')
            : Carbon::parse($dailyLog->work_date, 'Asia/Tehran');
        $this->assertEditable($date, $canManage);

        $employeeId = $dailyLog->user_id;
        $dateStr = $date->toDateString();
        $dailyLog->delete();

        return redirect()
            ->route('daily-logs.index', ['date' => $dateStr, 'user_id' => $employeeId])
            ->with('success', 'رویداد حذف شد.');
    }

    public function settings()
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        return view('daily-logs.settings', [
            'categories' => DailyLogCategory::query()->ordered()->get(),
            'options' => [
                'allow_past_days' => DailyLogSettings::allowPastDays(),
                'require_note' => DailyLogSettings::requireNote(),
                'show_quantity' => DailyLogSettings::showQuantity(),
            ],
        ]);
    }

    public function saveSettings(Request $request)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        $data = $request->validate([
            'allow_past_days' => ['required', 'integer', 'min:0', 'max:60'],
            'require_note' => ['nullable'],
            'show_quantity' => ['nullable'],
        ]);

        DailyLogSettings::save([
            'allow_past_days' => $data['allow_past_days'],
            'require_note' => $request->boolean('require_note'),
            'show_quantity' => $request->boolean('show_quantity'),
        ]);

        return back()->with('success', 'تنظیمات دفتر روز ذخیره شد.');
    }

    public function storeCategory(Request $request)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'hint' => ['nullable', 'string', 'max:255'],
            'mark' => ['nullable', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'ask_quantity' => ['nullable'],
            'requires_receipt' => ['nullable'],
            'is_active' => ['nullable'],
        ]);

        DailyLogCategory::create([
            'name' => $data['name'],
            'hint' => $data['hint'] ?? null,
            'mark' => $data['mark'] ?: '•',
            'sort_order' => (int) ($data['sort_order'] ?? 100),
            'ask_quantity' => $request->boolean('ask_quantity'),
            'requires_receipt' => $request->boolean('requires_receipt'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'دسته جدید اضافه شد.');
    }

    public function updateCategory(Request $request, DailyLogCategory $category)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'hint' => ['nullable', 'string', 'max:255'],
            'mark' => ['nullable', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'ask_quantity' => ['nullable'],
            'requires_receipt' => ['nullable'],
            'is_active' => ['nullable'],
        ]);

        $category->update([
            'name' => $data['name'],
            'hint' => $data['hint'] ?? null,
            'mark' => $data['mark'] ?: '•',
            'sort_order' => (int) ($data['sort_order'] ?? $category->sort_order),
            'ask_quantity' => $request->boolean('ask_quantity'),
            'requires_receipt' => $request->boolean('requires_receipt'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'دسته به‌روزرسانی شد.');
    }

    public function destroyCategory(DailyLogCategory $category)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);
        $category->delete();

        return back()->with('success', 'دسته حذف شد.');
    }

    private function validatedEntry(Request $request): array
    {
        $rules = [
            'daily_log_category_id' => ['nullable', 'integer', Rule::exists('daily_log_categories', 'id')],
            'reception_id' => ['nullable', 'integer', Rule::exists('receptions', 'id')],
            'title' => ['nullable', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'work_date' => ['nullable', 'date'],
        ];

        if (DailyLogSettings::requireNote()) {
            $rules['body'] = ['required', 'string', 'max:2000'];
        }

        return $request->validate($rules, [
            'body.required' => 'توضیح رویداد الزامی است.',
            'reception_id.exists' => 'قبض انتخاب‌شده معتبر نیست.',
        ]);
    }

    private function resolveReception(mixed $receptionId, ?DailyLogCategory $category, bool $requiredIfNeeded = true): ?Reception
    {
        $reception = null;
        if ($receptionId) {
            $reception = Reception::query()->find($receptionId);
        }

        $needs = $category && $category->needsReceipt();
        if ($needs && $requiredIfNeeded && ! $reception) {
            throw ValidationException::withMessages([
                'reception_id' => 'برای «'.($category->name).'» باید قبض را جستجو و انتخاب کنید.',
            ]);
        }

        return $reception;
    }

    private function defaultTitle(?DailyLogCategory $category, ?Reception $reception): string
    {
        if ($reception) {
            $ticket = $reception->ticket_no ?: $reception->receipt_no;
            $cat = $category?->name ?: 'کار روی قبض';

            return $cat.' — '.$ticket;
        }

        return $category?->name ?: 'رویداد روزانه';
    }

    private function resolveDate(mixed $value, ?Carbon $fallback = null): Carbon
    {
        try {
            if ($value) {
                $parsed = is_string($value) ? parse_jalali_or_gregorian_date($value) : null;
                if ($parsed) {
                    return Carbon::parse($parsed, 'Asia/Tehran')->startOfDay();
                }

                return Carbon::parse($value, 'Asia/Tehran')->startOfDay();
            }
        } catch (\Throwable) {
            // fall through
        }

        return ($fallback ?: now('Asia/Tehran'))->copy()->startOfDay();
    }

    private function dateIsEditable(Carbon $date, bool $canManage): bool
    {
        if ($canManage) {
            return true;
        }

        $today = now('Asia/Tehran')->startOfDay();
        $days = DailyLogSettings::allowPastDays();
        $min = $today->copy()->subDays($days);

        return $date->greaterThanOrEqualTo($min) && $date->lessThanOrEqualTo($today);
    }

    private function assertEditable(Carbon $date, bool $canManage): void
    {
        if ($this->dateIsEditable($date, $canManage)) {
            return;
        }

        throw ValidationException::withMessages([
            'work_date' => 'ثبت یا ویرایش این تاریخ برای شما مجاز نیست.',
        ]);
    }
}
