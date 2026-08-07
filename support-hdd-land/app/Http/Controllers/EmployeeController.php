<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use App\Models\Technician;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::query()->orderBy('name')->paginate(20);
        $all = User::query()->get(['id', 'is_active', 'can_login_otp', 'can_login_password']);

        return view('employees.index', [
            'employees' => $employees,
            'stats' => [
                'total' => $all->count(),
                'active' => $all->where('is_active', true)->count(),
                'otp' => $all->where('can_login_otp', true)->count(),
                'password' => $all->where('can_login_password', true)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('employees.create', [
            'permissions' => Permissions::ALL,
            'defaults' => Permissions::defaultsForRole('receptionist'),
        ]);
    }

    public function store(Request $request, NiazpardazSmsService $sms)
    {
        $data = $this->validated($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => User::normalizePhone($data['phone']),
            'password' => $data['password'] ?: str()->random(12),
            'role' => $data['role'],
            'permissions' => $data['role'] === 'admin'
                ? array_keys(Permissions::ALL)
                : ($data['permissions'] ?? Permissions::defaultsForRole($data['role'])),
            'can_login_otp' => $request->boolean('can_login_otp'),
            'can_login_password' => $request->boolean('can_login_password'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($data['role'] === 'technician') {
            Technician::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'specialty' => $data['specialty'] ?? null,
                    'commission_percent' => (int) ($data['commission_percent'] ?? 0),
                    'is_active' => true,
                ]
            );
        }

        $welcomeOk = true;
        $flash = 'کارمند ثبت شد.';
        if ($request->boolean('send_welcome_sms', true)) {
            $welcome = $this->sendWelcomeSms($sms, $user);
            $welcomeOk = (bool) ($welcome['ok'] ?? false);
            $flash .= $welcomeOk
                ? ' پیامک خوش‌آمدگویی ارسال شد.'
                : ' پیامک خوش‌آمدگویی ارسال نشد: '.($welcome['message'] ?? '');
        }

        return redirect()
            ->route('employees.index')
            ->with($welcomeOk ? 'success' : 'error', $flash);
    }

    public function edit(User $employee)
    {
        $employee->load('technician');

        return view('employees.edit', [
            'employee' => $employee,
            'permissions' => Permissions::ALL,
            'selected' => $employee->permissionList(),
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $data = $this->validated($request, $employee);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => User::normalizePhone($data['phone']),
            'role' => $data['role'],
            'permissions' => $employee->isAdmin() ? array_keys(Permissions::ALL) : ($data['permissions'] ?? []),
            'can_login_otp' => $request->boolean('can_login_otp'),
            'can_login_password' => $request->boolean('can_login_password'),
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $employee->update($payload);

        if ($data['role'] === 'technician') {
            Technician::updateOrCreate(
                ['user_id' => $employee->id],
                [
                    'name' => $employee->name,
                    'phone' => $employee->phone,
                    'specialty' => $data['specialty'] ?? null,
                    'commission_percent' => (int) ($data['commission_percent'] ?? 0),
                    'is_active' => (bool) $employee->is_active,
                ]
            );
        }

        return redirect()->route('employees.index')->with('success', 'اطلاعات کارمند به‌روزرسانی شد.');
    }

    public function destroy(User $employee)
    {
        if ((int) $employee->id === (int) auth()->id()) {
            return back()->withErrors(['employee' => 'نمی‌توانید حساب خودتان را حذف کنید.']);
        }

        if ($employee->isAdmin()) {
            $adminCount = User::query()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['employee' => 'حداقل یک مدیر باید در سیستم بماند.']);
            }
        }

        if ($employee->technician) {
            // Keep technician profile for historical receptions, but detach login
            $employee->technician->update(['user_id' => null, 'is_active' => false]);
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'کاربر/کارمند حذف شد.');
    }

    public function sendWelcome(User $employee, NiazpardazSmsService $sms)
    {
        $result = $this->sendWelcomeSms($sms, $employee);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'پیامک خوش‌آمدگویی برای '.$employee->name.' ارسال شد.'
                : 'ارسال پیامک ناموفق بود: '.($result['message'] ?? '')
        );
    }

    /** @return array{ok:bool,message:string} */
    private function sendWelcomeSms(NiazpardazSmsService $sms, User $user): array
    {
        $result = $sms->sendEmployeeWelcome($user);

        SmsLog::create([
            'reception_id' => null,
            'customer_id' => null,
            'sms_status_rule_id' => null,
            'sent_by' => Auth::id(),
            'phone' => User::normalizePhone($user->phone) ?: (string) $user->phone,
            'status_key' => 'employee_welcome',
            'audience' => 'employee',
            'message' => $result['text'] ?? '',
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
        ];
    }

    private function validated(Request $request, ?User $employee = null): array
    {
        $phone = User::normalizePhone((string) $request->input('phone', ''));
        $email = trim((string) $request->input('email', ''));
        $request->merge([
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
        ]);

        if ($phone) {
            $dupPhone = User::query()
                ->where('phone', $phone)
                ->when($employee, fn ($q) => $q->where('id', '!=', $employee->id))
                ->first();
            if ($dupPhone) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'phone' => "این موبایل قبلاً برای «{$dupPhone->name}» ثبت شده است. همان کارمند را ویرایش کنید یا شماره دیگری بزنید.",
                ]);
            }
        }

        if ($email !== '') {
            $dupEmail = User::query()
                ->where('email', $email)
                ->when($employee, fn ($q) => $q->where('id', '!=', $employee->id))
                ->first();
            if ($dupEmail) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'email' => "این ایمیل قبلاً برای «{$dupEmail->name}» ثبت شده است.",
                ]);
            }
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'role' => ['required', Rule::in(['admin', 'receptionist', 'technician', 'accountant', 'employee'])],
            'password' => ['nullable', 'string', 'min:6'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(Permissions::ALL))],
            'specialty' => ['nullable', 'string', 'max:120'],
            'commission_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [
            'name.required' => 'نام کامل الزامی است.',
            'email.email' => 'ایمیل معتبر نیست.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.min' => 'شماره موبایل معتبر نیست.',
            'role.required' => 'وظیفه / نقش را انتخاب کنید.',
            'role.in' => 'نقش انتخاب‌شده معتبر نیست.',
            'password.min' => 'رمز عبور حداقل ۶ کاراکتر باشد.',
        ], [
            'name' => 'نام کامل',
            'email' => 'ایمیل',
            'phone' => 'موبایل',
            'role' => 'وظیفه',
            'password' => 'رمز عبور',
        ]);

        return $data;
    }
}
