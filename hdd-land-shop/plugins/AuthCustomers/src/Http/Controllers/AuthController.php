<?php

namespace Plugins\AuthCustomers\src\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Plugins\AuthCustomers\Plugin;
use Plugins\AuthCustomers\src\Services\OtpService;
use Plugins\AuthCustomers\src\Services\SmsGateway;
use Plugins\AuthCustomers\src\Services\TotpAuthenticator;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        Plugin::ensureSchema();

        // مشتری لاگین‌شده به حساب برود؛ کارمند به پنل کارمند؛ ادمین بتواند صفحه را ببیند/تست کند
        if (Auth::check() && Auth::user() && ! Auth::user()->isAdmin()) {
            if (method_exists(Auth::user(), 'isStaff') && Auth::user()->isStaff()) {
                return redirect()->to(url('/staff'));
            }

            return redirect()->route('account.index');
        }

        return view('auth-customers::login', ['settings' => Plugin::settings()]);
    }

    public function login(Request $request): RedirectResponse
    {
        Plugin::ensureSchema();
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $login = trim($credentials['login']);
        $normalized = SmsGateway::normalizePhone($login);
        $user = User::query()->where(function ($q) use ($login, $normalized) {
            $q->whereRaw('LOWER(email) = ?', [Str::lower($login)])
                ->orWhereRaw('LOWER(username) = ?', [Str::lower($login)])
                ->orWhere('phone', $login)
                ->orWhere('phone', $normalized);
        })->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            try {
                if (class_exists(\Plugins\AdminCore\src\Support\StaffActivity::class)) {
                    \Plugins\AdminCore\src\Support\StaffActivity::log('login_failed', null, ['login' => $login], 'login', 'POST');
                }
            } catch (\Throwable) {
                //
            }

            return back()->withErrors(['login' => 'نام کاربری یا رمز عبور اشتباه است.'])->onlyInput('login');
        }

        if ($user->needsTwoFactor() && in_array($user->two_factor_method, ['sms', 'email', 'authenticator'], true)) {
            $request->session()->put('auth.2fa_user_id', $user->id);
            $request->session()->put('auth.2fa_remember', $request->boolean('remember'));

            if ($user->two_factor_method === 'sms' && $user->phone) {
                $issued = OtpService::issue($user->id, 'sms', SmsGateway::normalizePhone($user->phone), 'login_2fa');
                if (! $issued['ok']) {
                    return back()->with('error', $issued['message']);
                }
            } elseif ($user->two_factor_method === 'email') {
                $issued = OtpService::issue($user->id, 'email', $user->email, 'login_2fa');
                if (! $issued['ok']) {
                    return back()->with('error', $issued['message']);
                }
            }

            return redirect()->route('login.2fa')->with('success', 'کد تأیید را وارد کنید.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectAfterLogin($user);
    }

    public function showTwoFactor(): View|RedirectResponse
    {
        if (! session('auth.2fa_user_id')) {
            return redirect()->route('login');
        }
        $user = User::query()->find(session('auth.2fa_user_id'));

        return view('auth-customers::two-factor', [
            'method' => $user?->two_factor_method ?? 'email',
            'destination' => $user?->two_factor_method === 'sms'
                ? $this->maskPhone($user->phone)
                : ($user?->two_factor_method === 'email' ? $this->maskEmail($user->email) : 'Authenticator'),
        ]);
    }

    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:12']]);
        $userId = (int) session('auth.2fa_user_id');
        $user = User::query()->find($userId);
        if (! $user) {
            return redirect()->route('login')->with('error', 'نشست منقضی شد.');
        }

        $ok = false;
        if ($user->two_factor_method === 'authenticator' && $user->two_factor_secret) {
            $ok = TotpAuthenticator::verify($user->two_factor_secret, $request->input('code'));
        } elseif ($user->two_factor_method === 'sms' && $user->phone) {
            $ok = OtpService::verify($user->id, SmsGateway::normalizePhone($user->phone), 'login_2fa', $request->input('code'));
        } elseif ($user->two_factor_method === 'email') {
            $ok = OtpService::verify($user->id, $user->email, 'login_2fa', $request->input('code'));
        }

        if (! $ok) {
            return back()->with('error', 'کد تأیید نادرست یا منقضی است.');
        }

        $remember = (bool) session('auth.2fa_remember');
        $request->session()->forget(['auth.2fa_user_id', 'auth.2fa_remember']);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $this->redirectAfterLogin($user);
    }

    public function showRegister(): View|RedirectResponse
    {
        Plugin::ensureSchema();
        $s = Plugin::settings();

        if (Auth::check() && Auth::user() && ! Auth::user()->isAdmin()) {
            if (method_exists(Auth::user(), 'isStaff') && Auth::user()->isStaff()) {
                return redirect()->to(url('/staff'));
            }

            return redirect()->route('account.index');
        }

        if (isset($s['enable_registration']) && empty($s['enable_registration'])) {
            return redirect()->to(url('/login'))->with('error', 'ثبت‌نام فعلاً غیرفعال است.');
        }

        return view('auth-customers::register', [
            'settings' => $s,
            'provinces' => \Plugins\AuthCustomers\src\Support\IranLocations::provinces(),
            'citiesMap' => \Plugins\AuthCustomers\src\Support\IranLocations::map(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        Plugin::ensureSchema();
        $s = Plugin::settings();
        if (isset($s['enable_registration']) && empty($s['enable_registration'])) {
            return redirect()->to(url('/login'))->with('error', 'ثبت‌نام فعلاً غیرفعال است.');
        }

        $minLen = max(8, (int) ($s['password_min_length'] ?? 8));
        $passwordRules = ['required', 'string', 'min:'.$minLen, 'confirmed'];
        if (! empty($s['password_require_mixed'])) {
            $passwordRules[] = 'regex:/[a-z]/';
            $passwordRules[] = 'regex:/[A-Z]/';
        }
        if (! empty($s['password_require_number'])) {
            $passwordRules[] = 'regex:/[0-9]/';
        }
        if (! empty($s['password_require_symbol'])) {
            $passwordRules[] = 'regex:/[^A-Za-z0-9]/';
        }

        $rule = function (string $key, array $extra = []) use ($s): array {
            $shown = ! empty($s['show_'.$key]);
            $required = $shown && ! empty($s['require_'.$key]);
            if (! $shown) {
                return ['nullable'];
            }

            return array_merge([$required ? 'required' : 'nullable'], $extra);
        };

        $phoneRule = $rule('phone', ['string', 'max:30']);
        if (! empty($s['show_phone']) && ! empty($s['unique_phone'])) {
            $phoneRule[] = 'unique:users,phone';
        }

        $rules = [
            'name' => $rule('name', ['string', 'max:255']),
            'last_name' => $rule('last_name', ['string', 'max:120']),
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'email' => $rule('email', ['email', 'max:255', 'unique:users,email']),
            'phone' => $phoneRule,
            'national_id' => $rule('national_id', ['string', 'max:20']),
            'password' => $passwordRules,
            'address' => $rule('address', ['string', 'max:500']),
            'city' => $rule('city', ['string', 'max:120']),
            'province' => $rule('province', ['string', 'max:80']),
            'postal_code' => $rule('postal_code', ['string', 'max:20']),
            'birth_date' => $rule('birth_date', ['date']),
        ];
        // حداقل یکی از ایمیل یا موبایل برای حساب لازم است
        if (empty($s['show_email']) && empty($s['show_phone'])) {
            $rules['email'] = ['required', 'email', 'max:255', 'unique:users,email'];
        }
        if (! empty($s['require_terms'])) {
            $rules['terms'] = ['accepted'];
        }

        // Fast registration: only a mobile number and password are collected.
        // Profile and shipping data are completed later in the cabinet/checkout.
        $rules = [
            'phone' => ['required', 'string', 'max:30'],
            'password' => $passwordRules,
        ];

        $data = $request->validate($rules, [
            'password.regex' => 'رمز عبور با سیاست امنیتی فروشگاه مطابقت ندارد.',
            'terms.accepted' => 'پذیرش قوانین الزامی است.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
        ]);

        if (! empty($data['email'])) {
            $emailDomain = strtolower((string) substr(strrchr($data['email'], '@') ?: '', 1));
            $blocked = array_filter(array_map('trim', explode(',', (string) ($s['blocked_email_domains'] ?? ''))));
            if ($emailDomain && in_array($emailDomain, array_map('strtolower', $blocked), true)) {
                return back()->withErrors(['email' => 'این دامنه ایمیل مجاز نیست.'])->withInput();
            }
        }

        if (! empty($data['phone'])) {
            $data['phone'] = SmsGateway::normalizePhone($data['phone']);
            if (! preg_match('/^09\d{9}$/', $data['phone'])) {
                return back()->withErrors(['phone' => 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد.'])->withInput();
            }
            if (! empty($s['unique_phone']) && User::query()->where('phone', $data['phone'])->exists()) {
                return back()->withErrors(['phone' => 'این شماره موبایل قبلاً ثبت شده است.'])->withInput();
            }
        }

        if (! empty($data['province']) && ! \Plugins\AuthCustomers\src\Support\IranLocations::isValidProvince($data['province'])) {
            return back()->withErrors(['province' => 'استان نامعتبر است.'])->withInput();
        }
        if (! empty($data['province']) && ! empty($data['city'])
            && ! \Plugins\AuthCustomers\src\Support\IranLocations::isValidCity($data['province'], $data['city'])) {
            return back()->withErrors(['city' => 'شهر با استان انتخاب‌شده مطابقت ندارد.'])->withInput();
        }

        $first = trim((string) ($data['name'] ?? ''));
        $last = trim((string) ($data['last_name'] ?? ''));
        $fullName = trim($first.($last !== '' ? ' '.$last : ''));
        if ($fullName === '') {
            $fullName = ! empty($data['email']) ? strstr($data['email'], '@', true) : 'مشتری';
        }

        // ایمیل الزامی دیتابیس — اگر مخفی بود placeholder بساز
        $email = $data['email'] ?? null;
        if (empty($email) && ! empty($data['phone'])) {
            $email = $data['phone'].'@phone.local';
            while (User::query()->where('email', $email)->exists()) {
                $email = $data['phone'].'+'.Str::lower(Str::random(4)).'@phone.local';
            }
        }

        $user = User::query()->create([
            'name' => $fullName,
            'last_name' => $last !== '' ? $last : null,
            'username' => null,
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'password' => $data['password'],
            'role' => 'customer',
            'is_admin' => false,
            'two_factor_method' => 'none',
            'two_factor_enabled' => false,
            'terms_accepted_at' => ! empty($s['require_terms']) ? now() : null,
            'email_verified_at' => empty($s['verify_email_on_register']) ? now() : null,
        ]);

        if (! empty($s['auto_login_after_register'])) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        $needPhoneVerify = ! empty($user->phone);
        if ($needPhoneVerify) {
            if (! empty($s['sms_auto_send_on_register'])) {
                $issued = OtpService::issue($user->id, 'sms', SmsGateway::normalizePhone($user->phone), 'verify_phone');
                if (! $issued['ok']) {
                    // حساب ساخته شده؛ فقط هشدار SMS
                    $msg = 'حساب ساخته شد. ارسال SMS: '.$issued['message'];
                } else {
                    $msg = 'حساب ساخته شد. کد تأیید موبایل ارسال شد.';
                }
            } else {
                $msg = 'حساب ساخته شد. موبایل را تأیید کنید.';
            }

            if (! empty($s['auto_login_after_register'])) {
                return redirect()->route('account.verify.phone')->with('success', $msg ?? 'حساب ساخته شد.');
            }

            return redirect()->to(url('/login'))->with('success', $msg ?? 'حساب ساخته شد. وارد شوید و موبایل را تأیید کنید.');
        }

        if (! empty($s['auto_login_after_register'])) {
            return redirect()->route('account.index')->with('success', 'حساب شما ساخته شد.');
        }

        return redirect()->to(url('/login'))->with('success', 'حساب ساخته شد. وارد شوید.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $uid = Auth::id();
        $wasStaff = false;
        try {
            $u = Auth::user();
            $wasStaff = $u && (method_exists($u, 'isStaff') && $u->isStaff() || $u->isAdmin());
        } catch (\Throwable) {
            //
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($wasStaff && $uid) {
            try {
                \Plugins\AdminCore\src\Support\StaffActivity::log('logout', (int) $uid, [], 'logout', 'POST');
            } catch (\Throwable) {
                //
            }
        }

        return redirect()->route('home');
    }


    public function showSmsLogin(): View|RedirectResponse
    {
        Plugin::ensureSchema();

        if (Auth::check() && Auth::user() && ! Auth::user()->isAdmin()) {
            if (method_exists(Auth::user(), 'isStaff') && Auth::user()->isStaff()) {
                return redirect()->to(url('/staff'));
            }

            return redirect()->route('account.index');
        }

        if (request()->boolean('change')) {
            request()->session()->forget(['login_sms_sent', 'login_sms_phone', 'login_sms_user_id']);
        }

        return view('auth-customers::login-sms', [
            'settings' => Plugin::settings(),
            'otpSent' => (bool) session('login_sms_sent'),
            'phone' => session('login_sms_phone', old('phone')),
        ]);
    }

    public function sendSmsLogin(Request $request): RedirectResponse
    {
        Plugin::ensureSchema();
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = SmsGateway::normalizePhone($data['phone']);
        if (! preg_match('/^09\d{9}$/', $phone)) {
            return back()->withErrors(['phone' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.'])->withInput();
        }

        $user = User::query()->where('phone', $phone)->first();
        if (! $user) {
            return back()->withErrors(['phone' => 'حسابی با این شماره موبایل یافت نشد. ابتدا ثبت‌نام کنید.'])->withInput();
        }

        $issued = OtpService::issue($user->id, 'sms', $phone, 'login_sms');
        if (! $issued['ok']) {
            return back()->withErrors(['phone' => $issued['message']])->withInput();
        }

        $request->session()->put('login_sms_sent', true);
        $request->session()->put('login_sms_phone', $phone);
        $request->session()->put('login_sms_user_id', $user->id);

        $redirect = redirect()->route('login.sms')->with('success', 'کد تأیید پیامک شد.');
        if (config('app.debug') && ! empty($issued['code'])) {
            $redirect->with('debug_otp', $issued['code']);
        }

        return $redirect;
    }

    public function verifySmsLogin(Request $request): RedirectResponse
    {
        Plugin::ensureSchema();
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'max:12'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $phone = SmsGateway::normalizePhone($data['phone']);
        $userId = (int) session('login_sms_user_id');
        $user = $userId
            ? User::query()->where('id', $userId)->where('phone', $phone)->first()
            : User::query()->where('phone', $phone)->first();

        if (! $user) {
            return redirect()->route('login.sms')->withErrors(['phone' => 'نشست منقضی شد. دوباره شماره را وارد کنید.']);
        }

        $ok = OtpService::verify($user->id, $phone, 'login_sms', $data['code']);
        if (! $ok) {
            return back()->withErrors(['code' => 'کد تأیید نادرست یا منقضی است.'])->withInput();
        }

        $request->session()->forget(['login_sms_sent', 'login_sms_phone', 'login_sms_user_id']);

        // اگر ۲FA اپ Authenticator فعال است، بعد از SMS هم تأیید شود
        if ($user->needsTwoFactor() && $user->two_factor_method === 'authenticator') {
            $request->session()->put('auth.2fa_user_id', $user->id);
            $request->session()->put('auth.2fa_remember', $request->boolean('remember'));

            return redirect()->route('login.2fa')->with('success', 'کد Authenticator را وارد کنید.');
        }

        if ($user->needsTwoFactor() && $user->two_factor_method === 'email') {
            $request->session()->put('auth.2fa_user_id', $user->id);
            $request->session()->put('auth.2fa_remember', $request->boolean('remember'));
            $issued = OtpService::issue($user->id, 'email', $user->email, 'login_2fa');
            if (! $issued['ok']) {
                return back()->with('error', $issued['message']);
            }

            return redirect()->route('login.2fa')->with('success', 'کد ایمیل را وارد کنید.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectAfterLogin($user);
    }

    protected function redirectAfterLogin(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            try {
                \Plugins\AdminCore\src\Support\StaffActivity::log('login', $user->id, ['via' => 'main_login', 'role' => 'admin']);
            } catch (\Throwable) {
                //
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        if (method_exists($user, 'isStaff') && $user->isStaff()) {
            try {
                \Illuminate\Support\Facades\DB::table('staff_members')
                    ->where('user_id', $user->id)
                    ->update(['last_login_at' => now(), 'updated_at' => now()]);
                \Plugins\AdminCore\src\Support\StaffActivity::log('login', $user->id, ['via' => 'main_login']);
            } catch (\Throwable) {
                //
            }

            return redirect()->intended(url('/staff'));
        }

        $s = Plugin::settings();
        if (! empty($s['force_2fa']) && ! $user->two_factor_enabled) {
            return redirect()->route('account.security')
                ->with('error', 'فعال‌سازی ورود دو مرحله‌ای برای حساب شما الزامی است.');
        }

        return redirect()->intended(route('account.index'));
    }

    protected function maskPhone(?string $phone): string
    {
        $p = (string) $phone;
        if (strlen($p) < 7) {
            return '****';
        }

        return substr($p, 0, 4).'****'.substr($p, -2);
    }

    protected function maskEmail(string $email): string
    {
        [$n, $d] = array_pad(explode('@', $email, 2), 2, '');

        return substr($n, 0, 2).'***@'.$d;
    }
}
