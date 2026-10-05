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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $profile = $attendance->profileFor($user);

        if ($profile?->isSelfieApproved()) {
            return redirect()->route('attendance.index')
                ->with('success', 'سلفی شما قبلاً تأیید شده است.');
        }
        if ($profile?->isSelfiePending()) {
            return redirect()->route('attendance.index')
                ->with('success', 'سلفی شما در انتظار تأیید ادمین است.');
        }

        return view('attendance.onboard', [
            'settings' => AttendanceSettings::all(),
            'profile' => $profile,
            'officeReady' => AttendanceSettings::officeLat() !== null && AttendanceSettings::officeLng() !== null,
            'isResubmit' => $profile?->isSelfieRejected() ?? false,
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
            'face_detected' => ['required', 'in:1'],
        ], [
            'face_detected.required' => 'چهره در سلفی تشخیص داده نشد.',
            'face_detected.in' => 'چهره در سلفی تشخیص داده نشد.',
        ]);

        $attendance->completeOnboarding($request->user(), $request->file('photo'), $data);

        // اعلان به مدیران
        try {
            $admins = User::query()->where('role', 'admin')->where('is_active', true)->get();
            app(\App\Services\StaffNotifier::class)->notifyMany(
                $admins,
                'attendance_selfie',
                'سلفی حضور برای تأیید',
                'کارمند '.$request->user()->name.' سلفی حضور ارسال کرد؛ تأیید کنید.',
                route('attendance.manage')
            );
        } catch (\Throwable $e) {
            // ignore notify failures
        }

        $home = $request->user()->isIntern() ? 'intern.portal' : 'dashboard';

        return redirect()
            ->route($home)
            ->with('success', 'سلفی و GPS ثبت شد و برای تأیید ادمین ارسال شد. بعد از تأیید ادمین می‌توانید با سلفی ورود بزنید.');
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

    public function sendLinkSms(Request $request, AttendanceService $attendance, NiazpardazSmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'in:check_in,check_out'],
        ]);
        $result = $attendance->sendPunchLinkSms($request->user(), $data['purpose'], $sms);
        $msg = ! empty($result['ok']) ? 'لینک ورود/خروج پیامک شد.' : ('ارسال پیامک ناموفق: '.($result['message'] ?? 'خطا'));

        return back()->with(! empty($result['ok']) ? 'success' : 'error', $msg);
    }

    public function punch(Request $request, AttendanceService $attendance, NiazpardazSmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:check_in,check_out'],
            'method' => ['required', 'in:selfie,otp,photo_confirm,sms_link'],
            'otp_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'device_fingerprint' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'face_detected' => ['nullable', 'in:0,1'],
            'photo_confirmed' => ['nullable', 'in:0,1'],
            'punch_link_token' => ['nullable', 'string', 'max:64'],
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

    /** صفحه عمومی لینک پیامک — بدون لاگین */
    public function showPunchLink(string $token, AttendanceService $attendance): View|RedirectResponse
    {
        $link = \App\Models\AttendancePunchLink::query()->where('token', $token)->first();
        if (! $link) {
            return redirect()->route('login')->with('error', 'لینک نامعتبر است.');
        }

        if (session('attendance_link_done')) {
            return view('attendance.link-done', [
                'message' => session('success') ?: 'ثبت حضور انجام شد.',
                'employee' => $link->user,
            ]);
        }

        if (! $link->isValid()) {
            return view('attendance.link-done', [
                'message' => $link->isUsed()
                    ? 'این لینک قبلاً استفاده شده است.'
                    : 'این لینک منقضی شده است. دوباره از کارتابل حضور درخواست دهید.',
                'employee' => $link->user,
                'error' => true,
            ]);
        }

        $user = $link->user;
        $state = $attendance->todayState($user);
        $label = $link->purpose === AttendanceEvent::TYPE_OUT ? 'ثبت خروج' : 'ثبت ورود';

        return view('attendance.link', [
            'token' => $token,
            'link' => $link,
            'employee' => $user,
            'label' => $label,
            'settings' => AttendanceSettings::all(),
            'state' => $state,
            'profile' => $attendance->profileFor($user),
        ]);
    }

    public function submitPunchLink(Request $request, string $token, AttendanceService $attendance, NiazpardazSmsService $sms): RedirectResponse
    {
        try {
            $link = $attendance->findValidPunchLink($token);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('login')->with('error', $e->errors()['token'][0] ?? 'لینک نامعتبر است.');
        }

        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'device_fingerprint' => ['nullable', 'string', 'max:191'],
            'photo_confirmed' => ['nullable', 'in:0,1'],
        ]);

        $data['method'] = AttendanceEvent::METHOD_SMS_LINK;
        $data['punch_link_token'] = $token;

        try {
            $event = $attendance->punch(
                $link->user,
                $link->purpose,
                $data,
                null,
                $sms
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $msg = $event->typeLabel().' با موفقیت از طریق لینک پیامک ثبت شد.'
            .($event->status === AttendanceEvent::STATUS_FLAGGED ? ' (مشکوک: '.($event->flag_reason ?: '—').')' : '');

        return redirect()
            ->route('attendance.link.show', ['token' => $token])
            ->with('success', $msg)
            ->with('attendance_link_done', true);
    }

    public function myReferencePhoto(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user, 403);
        $profile = AttendanceProfile::query()->where('user_id', $user->id)->first();
        abort_unless($profile?->reference_photo_path, 404);

        return $this->serveLocalImage($profile->reference_photo_path);
    }

    public function punchLinkPhoto(string $token, AttendanceService $attendance): BinaryFileResponse
    {
        $link = \App\Models\AttendancePunchLink::query()->where('token', $token)->first();
        abort_unless($link && ($link->isValid() || session('attendance_link_done')), 404);
        $profile = $attendance->profileFor($link->user);
        abort_unless($profile?->reference_photo_path, 404);

        return $this->serveLocalImage($profile->reference_photo_path);
    }

    public function punchPhoto(AttendanceEvent $event): BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->id === (int) $event->user_id || $user->isAdmin() || $user->canAccess('attendance.manage')),
            403
        );
        abort_unless($event->photo_path, 404);

        return $this->serveLocalImage($event->photo_path);
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

        $pendingSelfies = AttendanceProfile::query()
            ->with(['user', 'enrolledByUser'])
            ->where('selfie_status', AttendanceProfile::SELFIE_PENDING)
            ->whereNotNull('reference_photo_path')
            ->orderByDesc('enrolled_at')
            ->get();

        return view('attendance.manage', [
            'settings' => AttendanceSettings::all(),
            'rows' => $rows,
            'events' => $events,
            'pendingSelfies' => $pendingSelfies,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'filterUserId' => $request->input('user_id'),
            'filterStatus' => $request->input('status'),
            'users' => $users,
        ]);
    }

    public function approveSelfie(Request $request, User $user, AttendanceService $attendance, NiazpardazSmsService $sms): RedirectResponse
    {
        $this->authorizeManage($request);
        $profile = AttendanceProfile::query()->where('user_id', $user->id)->firstOrFail();
        $attendance->approveSelfie($profile, $request->user());

        try {
            app(\App\Services\StaffNotifier::class)->notifyMany(
                [$user],
                'attendance_selfie',
                'سلفی حضور تأیید شد',
                'سلفی شما تأیید شد. لینک ورود امروز برایتان پیامک می‌شود.',
                route('attendance.index')
            );
        } catch (\Throwable $e) {
            // ignore
        }

        $smsResult = ['ok' => false, 'message' => ''];
        try {
            $smsResult = $attendance->notifySelfieApproved($user, $sms);
        } catch (\Throwable $e) {
            $smsResult = ['ok' => false, 'message' => $e->getMessage()];
        }

        $extra = ! empty($smsResult['ok'])
            ? ' لینک ورود امروز پیامک شد.'
            : (' اعلان داخل سیستم ثبت شد'.(! empty($smsResult['message']) ? '؛ SMS: '.$smsResult['message'] : '').'.');

        return back()->with('success', 'سلفی «'.$user->name.'» تأیید شد.'.$extra);
    }

    public function rejectSelfie(Request $request, User $user, AttendanceService $attendance): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $profile = AttendanceProfile::query()->where('user_id', $user->id)->firstOrFail();
        $attendance->rejectSelfie($profile, $request->user(), $data['reason'] ?? null);

        try {
            $reason = $data['reason'] ?? '';
            app(\App\Services\StaffNotifier::class)->notifyMany(
                [$user],
                'attendance_selfie',
                'سلفی حضور رد شد',
                'سلفی شما رد شد'.($reason !== '' ? ' ('.$reason.')' : '').'. لطفاً سلفی جدید ثبت کنید.',
                route('attendance.onboard')
            );
        } catch (\Throwable $e) {
            // ignore
        }

        return back()->with('success', 'سلفی «'.$user->name.'» رد شد.');
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

    public function referencePhoto(User $user): BinaryFileResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->canAccess('attendance.manage'), 403);
        $profile = AttendanceProfile::query()->where('user_id', $user->id)->first();
        abort_unless($profile?->reference_photo_path, 404);

        return $this->serveLocalImage($profile->reference_photo_path);
    }

    private function serveLocalImage(string $path): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);
        $full = Storage::disk('local')->path($path);
        abort_unless(is_file($full), 404);

        $mime = @mime_content_type($full) ?: 'image/jpeg';
        if (! str_starts_with($mime, 'image/')) {
            $mime = 'image/jpeg';
        }

        return response()->file($full, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->isAdmin() || $request->user()->canAccess('attendance.manage'), 403);
    }
}
