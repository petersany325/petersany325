<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Plugins\Studio\Plugin as StudioPlugin;
use Plugins\Studio\src\Support\StudioSms;

class StudioAuthController
{
    public function customer(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->afterLogin($request, Auth::user());
        }

        return $this->form($request, 'customer');
    }

    public function staff(Request $request): View|RedirectResponse
    {
        if (Auth::check() && $this->isStaffUser(Auth::user())) {
            return redirect()->to($this->staffHome());
        }

        return $this->form($request, 'staff');
    }

    public function sendCustomer(Request $request): RedirectResponse
    {
        $intent = $request->input('intent') === 'register' ? 'register' : 'login';
        $name = trim((string) $request->input('name', ''));
        if ($intent === 'register' && mb_strlen($name) < 2) {
            return back()->withInput()->with('error', 'برای ثبت‌نام نام و نام خانوادگی را بنویسید.');
        }

        $phone = StudioSms::normalizePhone((string) $request->input('phone', ''));
        if (! StudioSms::isMobile($phone)) {
            return back()->withInput()->with('error', 'شماره موبایل معتبر نیست. نمونه: 09121234567');
        }

        $user = $this->findUserByPhone($phone);
        if ($intent === 'login' && ! $user) {
            $this->rememberPending($request, 'customer', [
                'step' => 'phone',
                'phone' => $phone,
                'intent' => 'register',
                'name' => $name,
            ]);

            return back()->withInput()->with('error', 'حسابی با این شماره نیست. ثبت‌نام کنید تا کد پیامک شود.');
        }
        if ($intent === 'register' && $user && $this->isStaffUser($user)) {
            return back()->withInput()->with('error', 'این شماره برای کارمند ثبت شده. از ورود کارمند استفاده کنید.');
        }

        $sent = StudioSms::sendOtp($phone, 'customer');
        if (! $sent['ok']) {
            return back()->withInput()->with('error', $sent['message']);
        }

        $this->rememberPending($request, 'customer', [
            'step' => 'code',
            'phone' => $phone,
            'intent' => $intent,
            'name' => $name !== '' ? $name : (string) ($user->name ?? ''),
        ]);

        return back()->with('success', $sent['message']);
    }

    public function verifyCustomer(Request $request): RedirectResponse
    {
        $pending = $this->pending($request, 'customer');
        $phone = StudioSms::normalizePhone((string) ($request->input('phone') ?: ($pending['phone'] ?? '')));
        $code = (string) $request->input('code', '');
        $intent = ($request->input('intent') ?: ($pending['intent'] ?? 'login')) === 'register' ? 'register' : 'login';
        $name = trim((string) ($request->input('name') ?: ($pending['name'] ?? '')));

        $check = StudioSms::verifyOtp($phone, $code, 'customer');
        if (! $check['ok']) {
            $this->rememberPending($request, 'customer', [
                'step' => 'code',
                'phone' => $phone,
                'intent' => $intent,
                'name' => $name,
            ]);

            return back()->withInput()->with('error', $check['message']);
        }

        $user = $this->findUserByPhone($phone);
        if (! $user && $intent === 'login') {
            $this->rememberPending($request, 'customer', [
                'step' => 'phone',
                'phone' => $phone,
                'intent' => 'register',
                'name' => $name,
            ]);

            return back()->withInput()->with('error', 'حسابی با این شماره نیست. ثبت‌نام کنید.');
        }
        if (! $user) {
            if (mb_strlen($name) < 2) {
                $this->rememberPending($request, 'customer', [
                    'step' => 'phone',
                    'phone' => $phone,
                    'intent' => 'register',
                ]);

                return back()->withInput()->with('error', 'نام را برای ساخت حساب بنویسید.');
            }
            $user = $this->createCustomer($phone, $name);
        }

        $this->markPhoneVerified($user);
        $this->forgetPending($request, 'customer');
        $this->loginUser($request, $user);

        return $this->afterLogin($request, $user)->with('success', 'وارد شدید.');
    }

    public function sendStaff(Request $request): RedirectResponse
    {
        $phone = StudioSms::normalizePhone((string) $request->input('phone', ''));
        if (! StudioSms::isMobile($phone)) {
            return back()->withInput()->with('error', 'شماره موبایل معتبر نیست. نمونه: 09121234567');
        }

        $user = $this->findUserByPhone($phone);
        $allowed = $user && $this->isStaffUser($user);
        $sent = $allowed
            ? StudioSms::sendOtp($phone, 'staff')
            : ['ok' => true, 'message' => 'اگر این شماره برای کارمند ثبت شده باشد، کد پیامک می‌شود.'];
        if (! ($sent['ok'] ?? false)) {
            return back()->withInput()->with('error', $sent['message']);
        }

        $this->rememberPending($request, 'staff', [
            'step' => 'code',
            'phone' => $phone,
            'intent' => 'login',
        ]);

        return back()->with('success', $sent['message']);
    }

    public function verifyStaff(Request $request): RedirectResponse
    {
        $pending = $this->pending($request, 'staff');
        $phone = StudioSms::normalizePhone((string) ($request->input('phone') ?: ($pending['phone'] ?? '')));
        $code = (string) $request->input('code', '');
        $check = StudioSms::verifyOtp($phone, $code, 'staff');
        if (! $check['ok']) {
            $this->rememberPending($request, 'staff', [
                'step' => 'code',
                'phone' => $phone,
                'intent' => 'login',
            ]);

            return back()->withInput()->with('error', $check['message']);
        }

        $user = $this->findUserByPhone($phone);
        if (! $user || ! $this->isStaffUser($user)) {
            $this->forgetPending($request, 'staff');

            return back()->withInput()->with('error', 'این شماره برای کارمند فعال ثبت نشده است.');
        }

        $this->markPhoneVerified($user);
        $this->touchStaffLogin($user);
        $this->forgetPending($request, 'staff');
        $this->loginUser($request, $user);

        return redirect()->to($this->staffHome())->with('success', 'وارد پنل کارمند شدید.');
    }

    public function restart(Request $request): RedirectResponse
    {
        $mode = $request->input('mode') === 'staff' ? 'staff' : 'customer';
        $this->forgetPending($request, $mode);

        return redirect()->to($mode === 'staff' ? url('/auth/staff') : url('/auth/customer'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(url('/'))->with('success', 'خارج شدید.');
    }

    protected function form(Request $request, string $mode): View
    {
        StudioPlugin::ensureBooted();
        $pending = $this->pending($request, $mode);
        $tab = $request->query('tab', $pending['intent'] ?? 'login');
        if ($tab !== 'register') {
            $tab = 'login';
        }

        return view('studio::auth', [
            'mode' => $mode,
            'tab' => $tab,
            'step' => $pending['step'] ?? 'phone',
            'phone' => old('phone', $pending['phone'] ?? ''),
            'name' => old('name', $pending['name'] ?? ''),
            'title' => $mode === 'staff' ? 'ورود کارمند' : 'ورود و ثبت‌نام کاربر',
        ]);
    }

    /** @param  array<string,mixed>  $data */
    protected function rememberPending(Request $request, string $mode, array $data): void
    {
        $request->session()->put('studio_auth.'.$mode, $data);
    }

    /** @return array<string,mixed> */
    protected function pending(Request $request, string $mode): array
    {
        $raw = $request->session()->get('studio_auth.'.$mode, []);

        return is_array($raw) ? $raw : [];
    }

    protected function forgetPending(Request $request, string $mode): void
    {
        $request->session()->forget('studio_auth.'.$mode);
    }

    protected function findUserByPhone(string $phone): ?User
    {
        try {
            if (! Schema::hasTable('users')) {
                return null;
            }
            $q = User::query();
            if (Schema::hasColumn('users', 'phone')) {
                $q->whereIn('phone', StudioSms::phoneAliases($phone));
            } else {
                return null;
            }

            return $q->first();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function createCustomer(string $phone, string $name): User
    {
        $user = new User;
        $user->name = mb_substr($name, 0, 120);
        $user->phone = $phone;
        if (Schema::hasColumn('users', 'email')) {
            $user->email = 'c.'.$phone.'@customers.hdd-land.local';
        }
        if (Schema::hasColumn('users', 'role')) {
            $user->role = 'customer';
        }
        if (Schema::hasColumn('users', 'is_admin')) {
            $user->is_admin = false;
        }
        $user->password = Str::random(24);
        if (Schema::hasColumn('users', 'phone_verified_at')) {
            $user->phone_verified_at = now();
        }
        $user->save();

        return $user;
    }

    protected function markPhoneVerified(User $user): void
    {
        try {
            if (Schema::hasColumn('users', 'phone_verified_at') && empty($user->phone_verified_at)) {
                $user->phone_verified_at = now();
                $user->save();
            }
        } catch (\Throwable) {
        }
    }

    protected function touchStaffLogin(User $user): void
    {
        try {
            if (Schema::hasTable('staff_members') && Schema::hasColumn('staff_members', 'last_login_at')) {
                DB::table('staff_members')->where('user_id', $user->id)->update([
                    'last_login_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable) {
        }
    }

    protected function loginUser(Request $request, User $user): void
    {
        Auth::login($user, true);
        $request->session()->regenerate();
    }

    protected function isStaffUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        try {
            return $user->isAdmin() || $user->isStaff();
        } catch (\Throwable) {
            return (bool) ($user->is_admin || in_array((string) $user->role, ['staff', 'admin'], true));
        }
    }

    protected function afterLogin(Request $request, User $user): RedirectResponse
    {
        if ($this->isStaffUser($user) && $request->is('auth/staff*')) {
            return redirect()->to($this->staffHome());
        }
        if ($this->isStaffUser($user) && ! $request->is('auth/customer*')) {
            return redirect()->to($this->staffHome());
        }
        foreach (['account.index', 'account'] as $name) {
            try {
                if (app('router')->has($name)) {
                    return redirect()->route($name);
                }
            } catch (\Throwable) {
            }
        }

        return redirect()->to(url('/account'));
    }

    protected function staffHome(): string
    {
        return url('/staff');
    }
}
