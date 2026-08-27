<?php

namespace App\Http\Controllers;

use App\Models\DailyLogCategory;
use App\Models\DailyLogEntry;
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
            ->get()
            ->keyBy('id');

        foreach ($byEmployee as $row) {
            $row->setRelation('user', $usersById->get($row->user_id));
        }

        return view('daily-logs.report', [
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'byEmployee' => $byEmployee,
            'employees' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employeeId' => $employeeId,
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
}
