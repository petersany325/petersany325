<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEvent;
use App\Models\AttendanceProfile;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\NiazpardazSmsService;
use App\Support\AttendanceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $attendance): View
    {
        $user = $request->user();
        $state = $attendance->todayState($user);
        $profile = $attendance->profileFor($user);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $days = $attendance->presentDaysCount($user, $from, $to);
        $summary = $attendance->dailySummary($user, $from, $to);
        $recent = AttendanceEvent::query()
            ->where('user_id', $user->id)
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        return view('attendance.index', [
            'settings' => AttendanceSettings::all(),
            'state' => $state,
            'profile' => $profile,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'presentDays' => $days,
            'summary' => $summary,
            'recent' => $recent,
            'canManage' => $user->isAdmin() || $user->canAccess('attendance.manage'),
        ]);
    }

    public function onboard(Request $request, AttendanceService $attendance): View|RedirectResponse
    {
        $user = $request->user();
        if ($attendance->hasCompletedOnboarding($user)) {
            return redirect()->route('attendance.index')
                ->with('success', 'ثبت اولیه حضور قبلاً انجام شده است.');
        }

        return view('attendance.onboard', [
            'settings' => AttendanceSettings::all(),
            'profile' => $attendance->profileFor($user),
            'officeReady' => AttendanceSettings::officeLat() !== null && AttendanceSettings::officeLng() !== null,
        ]);
    }

    public function onboardStore(Request $request, AttendanceService $attendance): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'device_fingerprint' => ['nullable', 'string', 'max:191'],
        ]);

        $attendance->completeOnboarding($request->user(), $request->file('photo'), $data);

        $home = $request->user()->isIntern() ? 'intern.portal' : 'dashboard';

        return redirect()
            ->route($home)
            ->with('success', 'ثبت اولیه سلفی و GPS موبایل انجام شد. منوی کار آزاد شد — از میانبر «حضور» ورود/خروج بزنید.');
    }

    public function sendOtp(Request $request, AttendanceService $attendance, NiazpardazSmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'in:check_in,check_out'],
        ]);
        $result = $attendance->sendPunchOtp($request->user(), $data['purpose'], $sms);
        $msg = ! empty($result['ok']) ? 'کد تأیید پیامک شد.' : ('ارسال پیامک ناموفق: '.($result['message'] ?? 'خطا'));
        if (! empty($result['debug_code'])) {
            $msg .= ' (کد آزمایشی: '.$result['debug_code'].')';
        }

        return back()->with(! empty($result['ok']) || ! empty($result['debug_code']) ? 'success' : 'error', $msg)
            ->with('attendance_purpose', $data['purpose'])
            ->with('attendance_method', 'otp');
    }

    public function punch(Request $request, AttendanceService $attendance, NiazpardazSmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:check_in,check_out'],
            'method' => ['required', 'in:selfie,otp'],
            'otp_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'device_fingerprint' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $event = $attendance->punch(
            $request->user(),
            $data['type'],
            $data,
            $request->file('photo'),
            $sms
        );

        $label = $event->typeLabel();
        $extra = $event->status === AttendanceEvent::STATUS_FLAGGED
            ? ' — ثبت شد ولی مشکوک علامت خورد: '.($event->flag_reason ?: '—')
            : '';

        return back()->with('success', $label.' با موفقیت ثبت شد.'.$extra);
    }

    public function punchPhoto(AttendanceEvent $event): Response
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->id === (int) $event->user_id || $user->isAdmin() || $user->canAccess('attendance.manage')),
            403
        );
        abort_unless($event->photo_path && Storage::disk('local')->exists($event->photo_path), 404);

        return Storage::disk('local')->response($event->photo_path);
    }

    public function manage(Request $request, AttendanceService $attendance): View
    {
        $this->authorizeManage($request);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $users = User::query()
            ->where('is_active', true)
            ->where('role', '!=', 'intern')
            ->orderBy('name')
            ->get();

        $profiles = AttendanceProfile::query()->with('enrolledByUser')->get()->keyBy('user_id');

        $rows = [];
        foreach ($users as $u) {
            $rows[] = [
                'user' => $u,
                'profile' => $profiles->get($u->id),
                'present_days' => $attendance->presentDaysCount($u, $from, $to),
                'today' => $attendance->todayState($u),
            ];
        }

        $events = AttendanceEvent::query()
            ->with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (int) $request->input('user_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->whereBetween('occurred_at', [$from, $to])
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();

        return view('attendance.manage', [
            'settings' => AttendanceSettings::all(),
            'rows' => $rows,
            'events' => $events,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'filterUserId' => $request->input('user_id'),
            'filterStatus' => $request->input('status'),
            'users' => $users,
        ]);
    }

    public function settings(): View
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->canAccess('attendance.manage'), 403);

        return view('attendance.settings', [
            'settings' => AttendanceSettings::all(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->canAccess('attendance.manage'), 403);

        $data = $request->validate([
            'enabled' => ['nullable'],
            'require_gps' => ['nullable'],
            'require_inside_geofence' => ['nullable'],
            'office_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'office_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'office_label' => ['nullable', 'string', 'max:255'],
            'geofence_radius_m' => ['nullable', 'integer', 'min:20', 'max:5000'],
            'max_gps_accuracy_m' => ['nullable', 'integer', 'min:5', 'max:500'],
            'allow_selfie' => ['nullable'],
            'allow_otp' => ['nullable'],
            'require_enrolled_photo' => ['nullable'],
            'work_start' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work_end' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'late_after_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'min_minutes_between_punches' => ['nullable', 'integer', 'min:0', 'max:120'],
            'bind_device' => ['nullable'],
            'require_onboarding' => ['nullable'],
        ]);

        AttendanceSettings::save($data);

        return back()->with('success', 'تنظیمات حضور و غیاب ذخیره شد.');
    }

    /**
     * جستجوی آنلاین آدرس/محل برای تنظیم GPS شرکت (Nominatim).
     */
    public function geocodeSearch(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->canAccess('attendance.manage'), 403);

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json(['ok' => false, 'message' => 'حداقل ۳ حرف برای جستجو لازم است.', 'results' => []], 422);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'HDDLandAttendance/1.3 (customer GPS setup)',
                    'Accept-Language' => 'fa,en',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $q,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 8,
                    'countrycodes' => 'ir',
                ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'خطا در ارتباط با سرویس نقشه.', 'results' => []], 502);
        }

        if (! $response->successful()) {
            return response()->json(['ok' => false, 'message' => 'سرویس جستجوی نقشه در دسترس نیست.', 'results' => []], 502);
        }

        $results = [];
        foreach ($response->json() ?: [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $lat = isset($row['lat']) ? (float) $row['lat'] : null;
            $lng = isset($row['lon']) ? (float) $row['lon'] : null;
            if ($lat === null || $lng === null) {
                continue;
            }
            $results[] = [
                'lat' => round($lat, 7),
                'lng' => round($lng, 7),
                'label' => (string) ($row['display_name'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
            ];
        }

        return response()->json(['ok' => true, 'results' => $results]);
    }

    public function enrollForm(User $user): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $profile = AttendanceProfile::query()->where('user_id', $user->id)->first();

        return view('attendance.enroll', [
            'employee' => $user,
            'profile' => $profile,
        ]);
    }

    public function enrollStore(Request $request, User $user, AttendanceService $attendance): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $attendance->enrollPhoto($user, $data['photo'], $request->user());

        return redirect()
            ->route('attendance.manage')
            ->with('success', 'عکس مرجع «'.$user->name.'» فقط توسط ادمین ثبت شد.');
    }

    public function referencePhoto(User $user): Response
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->canAccess('attendance.manage'), 403);
        $profile = AttendanceProfile::query()->where('user_id', $user->id)->first();
        abort_unless($profile?->reference_photo_path && Storage::disk('local')->exists($profile->reference_photo_path), 404);

        return Storage::disk('local')->response($profile->reference_photo_path);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->isAdmin() || $request->user()->canAccess('attendance.manage'), 403);
    }
}
