<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use App\Support\Permissions;
use App\Support\StaffSmsTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InternController extends Controller
{
    public function index()
    {
        $interns = Intern::query()
            ->with('user')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20);
        $all = Intern::query()->get(['id', 'is_active', 'start_date', 'end_date', 'user_id']);
        $today = now('Asia/Tehran')->startOfDay();

        return view('interns.index', [
            'interns' => $interns,
            'stats' => [
                'total' => $all->count(),
                'active' => $all->where('is_active', true)->filter(fn ($i) => $i->start_date <= $today && $i->end_date >= $today)->count(),
                'upcoming' => $all->where('is_active', true)->filter(fn ($i) => $i->start_date > $today)->count(),
                'finished' => $all->filter(fn ($i) => $i->end_date < $today || ! $i->is_active)->count(),
                'portal' => $all->whereNotNull('user_id')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('interns.create', [
            'permissions' => Permissions::INTERN_MANAGEABLE,
            'selected' => Permissions::defaultsForRole('intern'),
        ]);
    }

    public function store(Request $request, NiazpardazSmsService $sms)
    {
        $data = $this->validated($request);

        $intern = Intern::create([
            'name' => $data['name'],
            'phone' => User::normalizePhone($data['phone']),
            'email' => $data['email'] ?: null,
            'national_code' => $data['national_code'] ?: null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'department' => $data['department'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        $this->syncPortalUser($request, $intern);

        $flash = 'کارآموز ثبت شد.';
        $ok = true;
        if ($request->boolean('send_welcome_sms', true)) {
            $result = $this->sendWelcome($sms, $intern);
            $ok = $result['ok'];
            $flash .= $ok
                ? ' پیامک خوش‌آمدگویی ارسال شد.'
                : ' پیامک ارسال نشد: '.($result['message'] ?? '');
        }

        return redirect()->route('interns.index')->with($ok ? 'success' : 'error', $flash);
    }

    public function edit(Intern $intern)
    {
        $intern->load('user');

        return view('interns.edit', [
            'intern' => $intern,
            'permissions' => Permissions::INTERN_MANAGEABLE,
            'selected' => $intern->user
                ? $intern->user->permissionList()
                : Permissions::defaultsForRole('intern'),
        ]);
    }

    public function update(Request $request, Intern $intern)
    {
        $data = $this->validated($request, $intern);

        $intern->update([
            'name' => $data['name'],
            'phone' => User::normalizePhone($data['phone']),
            'email' => $data['email'] ?: null,
            'national_code' => $data['national_code'] ?: null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'department' => $data['department'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->syncPortalUser($request, $intern);

        return redirect()->route('interns.index')->with('success', 'اطلاعات کارآموز به‌روزرسانی شد.');
    }

    public function destroy(Intern $intern)
    {
        if ($intern->user) {
            $intern->user->update([
                'is_active' => false,
                'can_login_otp' => false,
                'can_login_password' => false,
            ]);
        }
        $intern->delete();

        return redirect()->route('interns.index')->with('success', 'کارآموز حذف شد.');
    }

    public function sendWelcomeSms(Intern $intern, NiazpardazSmsService $sms)
    {
        $result = $this->sendWelcome($sms, $intern);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'پیامک خوش‌آمدگویی برای '.$intern->name.' ارسال شد.'
                : 'ارسال پیامک ناموفق بود: '.($result['message'] ?? '')
        );
    }

    private function syncPortalUser(Request $request, Intern $intern): void
    {
        $enablePortal = $request->boolean('portal_enabled');
        $canOtp = $request->boolean('can_login_otp');
        $canPass = $request->boolean('can_login_password');
        $perms = array_values(array_intersect(
            $request->input('permissions', Permissions::defaultsForRole('intern')),
            array_keys(Permissions::INTERN_MANAGEABLE)
        ));
        if (! in_array('profile', $perms, true)) {
            $perms[] = 'profile';
        }
        if (! in_array('dashboard', $perms, true)) {
            $perms[] = 'dashboard';
        }

        if (! $enablePortal) {
            if ($intern->user) {
                $intern->user->update([
                    'is_active' => false,
                    'can_login_otp' => false,
                    'can_login_password' => false,
                    'permissions' => $perms,
                ]);
            }

            return;
        }

        $phone = User::normalizePhone($intern->phone);
        $password = $request->input('password');

        $user = $intern->user;
        if (! $user && $phone) {
            $user = User::query()->where('phone', $phone)->first();
        }

        $payload = [
            'name' => $intern->name,
            'email' => $intern->email,
            'phone' => $phone,
            'role' => 'intern',
            'permissions' => $perms,
            'can_login_otp' => $canOtp,
            'can_login_password' => $canPass || filled($password),
            'is_active' => (bool) $intern->is_active && $intern->isCurrent(),
        ];

        // Keep login active during internship window even if isCurrent edge-case on same-day create
        if ($intern->is_active) {
            $payload['is_active'] = true;
        }

        if ($user) {
            if (filled($password)) {
                $payload['password'] = $password;
            }
            // Don't overwrite non-intern accounts accidentally
            if ($user->role !== 'intern' && (int) $user->id !== (int) $intern->user_id) {
                // Create dedicated intern account instead of hijacking employee
                $user = null;
            } else {
                $user->update($payload);
            }
        }

        if (! $user) {
            $user = User::create(array_merge($payload, [
                'password' => $password ?: str()->random(16),
                'email' => $intern->email ?: ('intern'.$intern->id.'@hdd-land.local'),
            ]));
        }

        $intern->forceFill(['user_id' => $user->id])->save();
    }

    /** @return array{ok:bool,message:string} */
    private function sendWelcome(NiazpardazSmsService $sms, Intern $intern): array
    {
        $phone = User::normalizePhone($intern->phone);
        if (! $phone) {
            return ['ok' => false, 'message' => 'شماره موبایل معتبر نیست.'];
        }

        $message = StaffSmsTemplates::renderIntern($intern);
        $result = $sms->send($phone, $message);

        SmsLog::create([
            'reception_id' => null,
            'customer_id' => null,
            'sms_status_rule_id' => null,
            'sent_by' => Auth::id(),
            'phone' => $phone,
            'status_key' => 'intern_welcome',
            'audience' => 'intern',
            'message' => $message,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
        ];
    }

    private function validated(Request $request, ?Intern $intern = null): array
    {
        $phone = User::normalizePhone((string) $request->input('phone', ''));
        $request->merge(['phone' => $phone]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'department' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(Permissions::INTERN_MANAGEABLE))],
        ], [
            'name.required' => 'نام کارآموز الزامی است.',
            'phone.required' => 'موبایل الزامی است.',
            'start_date.required' => 'تاریخ شروع کارآموزی الزامی است.',
            'end_date.required' => 'تاریخ پایان کارآموزی الزامی است.',
            'end_date.after_or_equal' => 'تاریخ پایان باید بعد از شروع باشد.',
        ]);
    }
}
