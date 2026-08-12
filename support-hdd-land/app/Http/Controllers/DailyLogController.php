<?php

namespace App\Http\Controllers;

use App\Models\DailyLogCategory;
use App\Models\DailyLogCheck;
use App\Models\DailyLogEntry;
use App\Models\User;
use App\Support\DailyLogSettings;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            ->with(['category', 'creator'])
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
        ];

        return view('daily-logs.index', [
            'date' => $date,
            'employee' => $employee,
            'entries' => $entries,
            'categories' => $categories,
            'employees' => $employees,
            'canManage' => $canManage,
            'summary' => $summary,
            'settings' => [
                'require_note' => DailyLogSettings::requireNote(),
                'show_quantity' => DailyLogSettings::showQuantity(),
                'allow_past_days' => DailyLogSettings::allowPastDays(),
                'editable' => $this->dateIsEditable($date, $canManage),
            ],
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
        $statusFilter = (string) $request->input('status', 'all'); // all|missing|partial|complete|unchecked
        $minEntries = DailyLogSettings::minEntriesPerDay();

        $staff = $this->getDailyLogStaff($employeeId);

        $workDays = $this->workDaysBetween($from, $to);
        $counts = DailyLogEntry::query()
            ->selectRaw('user_id, work_date, COUNT(*) as cnt, COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(minutes),0) as mins')
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->when($employeeId, fn ($q) => $q->where('user_id', $employeeId))
            ->groupBy('user_id', 'work_date')
            ->get()
            ->keyBy(fn ($row) => $row->user_id.'|'.Carbon::parse($row->work_date)->toDateString());

        $checks = DailyLogCheck::query()
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->when($employeeId, fn ($q) => $q->where('user_id', $employeeId))
            ->with('checker:id,name')
            ->get()
            ->keyBy(fn ($row) => $row->user_id.'|'.$row->work_date->toDateString());

        $checklist = [];
        $stats = [
            'slots' => 0,
            'complete' => 0,
            'partial' => 0,
            'missing' => 0,
            'checked' => 0,
            'issues' => 0,
        ];

        foreach ($staff as $person) {
            foreach ($workDays as $day) {
                $key = $person->id.'|'.$day;
                $row = $counts->get($key);
                $cnt = (int) ($row->cnt ?? 0);
                $fill = $cnt >= $minEntries ? 'complete' : ($cnt > 0 ? 'partial' : 'missing');
                $check = $checks->get($key);
                $item = [
                    'user' => $person,
                    'date' => $day,
                    'count' => $cnt,
                    'quantity' => (int) ($row->qty ?? 0),
                    'minutes' => (int) ($row->mins ?? 0),
                    'fill' => $fill,
                    'check' => $check,
                    'checked' => (bool) $check,
                ];
                $stats['slots']++;
                $stats[$fill]++;
                if ($check) {
                    $stats['checked']++;
                    if ($check->status === 'issue') {
                        $stats['issues']++;
                    }
                }

                if ($statusFilter === 'missing' && $fill !== 'missing') {
                    continue;
                }
                if ($statusFilter === 'partial' && $fill !== 'partial') {
                    continue;
                }
                if ($statusFilter === 'complete' && $fill !== 'complete') {
                    continue;
                }
                if ($statusFilter === 'unchecked' && $check) {
                    continue;
                }

                $checklist[] = $item;
            }
        }

        // newest dates first for manager review
        usort($checklist, function ($a, $b) {
            $d = strcmp($b['date'], $a['date']);
            if ($d !== 0) {
                return $d;
            }

            return strcmp($a['user']->name, $b['user']->name);
        });

        $query = DailyLogEntry::query()
            ->with(['user', 'category'])
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->orderByDesc('work_date')
            ->orderByDesc('id');

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        }

        $entries = $query->paginate(40)->withQueryString();

        $byEmployee = DailyLogEntry::query()
            ->selectRaw('user_id, COUNT(*) as cnt, COALESCE(SUM(quantity),0) as qty')
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->when($employeeId, fn ($q) => $q->where('user_id', $employeeId))
            ->groupBy('user_id')
            ->get();

        $usersById = User::query()
            ->whereIn('id', $byEmployee->pluck('user_id')->filter()->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        foreach ($byEmployee as $row) {
            $row->setRelation('user', $usersById->get($row->user_id));
        }

        return view('daily-logs.report', [
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'byEmployee' => $byEmployee,
            'employees' => $this->getDailyLogStaff(),
            'employeeId' => $employeeId,
            'statusFilter' => $statusFilter,
            'checklist' => $checklist,
            'stats' => $stats,
            'minEntries' => $minEntries,
            'workDaysCount' => count($workDays),
            'exemptNote' => DailyLogSettings::skipFridays() ? 'جمعه‌ها و تعطیلی‌های اعلامی از چک خارج‌اند.' : 'تعطیلی‌های اعلامی از چک خارج‌اند.',
        ]);
    }

    public function check(Request $request)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'work_date' => ['required', 'string'],
            'status' => ['required', Rule::in(['reviewed', 'issue'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $date = $this->resolveDate($data['work_date']);
        if (DailyLogSettings::isExemptDay($date)) {
            return back()->with('error', 'این روز تعطیل/معاف است و نیاز به چک ندارد.');
        }

        DailyLogCheck::query()->updateOrCreate(
            [
                'user_id' => (int) $data['user_id'],
                'work_date' => $date->toDateString(),
            ],
            [
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'checked_by' => auth()->id(),
                'checked_at' => now('Asia/Tehran'),
            ]
        );

        return back()->with('success', $data['status'] === 'issue' ? 'به‌عنوان نیاز به پیگیری ثبت شد.' : 'بررسی دفتر روز ثبت شد.');
    }

    public function uncheck(Request $request)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'work_date' => ['required', 'string'],
        ]);

        $date = $this->resolveDate($data['work_date']);
        DailyLogCheck::query()
            ->where('user_id', (int) $data['user_id'])
            ->whereDate('work_date', $date->toDateString())
            ->delete();

        return back()->with('success', 'علامت بررسی برداشته شد.');
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

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = $category?->name ?: 'رویداد روزانه';
        }

        DailyLogEntry::create([
            'user_id' => $employeeId,
            'work_date' => $date->toDateString(),
            'daily_log_category_id' => $category?->id,
            'category_name' => $category?->name,
            'title' => $title,
            'body' => $data['body'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'minutes' => $data['minutes'] ?? null,
            'created_by' => $actor->id,
        ]);

        return redirect()
            ->route('daily-logs.index', ['date' => $date->toDateString(), 'user_id' => $employeeId])
            ->with('success', 'رویداد در دفتر روز ثبت شد.');
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

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = $category?->name ?: ($dailyLog->category_name ?: 'رویداد روزانه');
        }

        $dailyLog->update([
            'daily_log_category_id' => $category?->id,
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
                'min_entries' => DailyLogSettings::minEntriesPerDay(),
                'skip_fridays' => DailyLogSettings::skipFridays(),
                'closed_dates' => DailyLogSettings::closedDatesRaw(),
            ],
        ]);
    }

    public function saveSettings(Request $request)
    {
        abort_unless(auth()->user()->canAccess('daily_logs.manage'), 403);

        $data = $request->validate([
            'allow_past_days' => ['required', 'integer', 'min:0', 'max:60'],
            'min_entries' => ['required', 'integer', 'min:1', 'max:20'],
            'require_note' => ['nullable'],
            'show_quantity' => ['nullable'],
            'skip_fridays' => ['nullable'],
            'closed_dates' => ['nullable', 'string', 'max:2000'],
        ]);

        DailyLogSettings::save([
            'allow_past_days' => $data['allow_past_days'],
            'min_entries' => $data['min_entries'],
            'require_note' => $request->boolean('require_note'),
            'show_quantity' => $request->boolean('show_quantity'),
            'skip_fridays' => $request->boolean('skip_fridays'),
            'closed_dates' => $data['closed_dates'] ?? '',
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
            'is_active' => ['nullable'],
        ]);

        DailyLogCategory::create([
            'name' => $data['name'],
            'hint' => $data['hint'] ?? null,
            'mark' => $data['mark'] ?: '•',
            'sort_order' => (int) ($data['sort_order'] ?? 100),
            'ask_quantity' => $request->boolean('ask_quantity'),
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
            'is_active' => ['nullable'],
        ]);

        $category->update([
            'name' => $data['name'],
            'hint' => $data['hint'] ?? null,
            'mark' => $data['mark'] ?: '•',
            'sort_order' => (int) ($data['sort_order'] ?? $category->sort_order),
            'ask_quantity' => $request->boolean('ask_quantity'),
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
        ]);
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

    /** @return \Illuminate\Database\Eloquent\Builder<\App\Models\User> */
    private function dailyLogStaffQuery()
    {
        // فیلتر دقیق دسترسی در getDailyLogStaff انجام می‌شود
        return User::query()
            ->where('is_active', true)
            ->where('role', '!=', 'admin')
            ->orderBy('name');
    }

    /** @return Collection<int, User> */
    private function getDailyLogStaff(?int $employeeId = null): Collection
    {
        return $this->dailyLogStaffQuery()
            ->when($employeeId, fn ($q) => $q->where('id', $employeeId))
            ->get(['id', 'name', 'role', 'permissions'])
            ->filter(fn (User $u) => $u->canAccess('daily_logs'))
            ->values();
    }

    /** @return list<string> */
    private function workDaysBetween(Carbon $from, Carbon $to): array
    {
        $days = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            /** @var Carbon $day */
            if (DailyLogSettings::isExemptDay($day)) {
                continue;
            }
            $days[] = $day->toDateString();
        }

        return $days;
    }
}
