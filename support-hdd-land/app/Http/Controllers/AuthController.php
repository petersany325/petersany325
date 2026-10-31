<?php

namespace App\Http\Controllers;

use App\Models\LoginOtp;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $phone = User::normalizePhone($login);

        $user = User::query()
            ->where(function ($q) use ($login, $phone) {
                $q->where('email', $login);
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->first();

        if (! $user) {
            return back()->withErrors(['login' => 'کاربری با این ایمیل/موبایل یافت نشد.'])->onlyInput('login');
        }

        if (! $user->is_active) {
            return back()->withErrors(['login' => 'حساب کاربری غیرفعال است.'])->onlyInput('login');
        }

        if (! $user->can_login_password) {
            return back()->withErrors(['login' => 'ورود با رمز برای این کاربر غیرفعال است. از SMS استفاده کنید.'])->onlyInput('login');
        }

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['login' => 'رمز عبور اشتباه است.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute()));
    }

    public function sendOtp(Request $request, NiazpardazSmsService $sms)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = User::normalizePhone($data['phone']);
        if (! $phone || strlen($phone) < 10) {
            return back()->withErrors(['phone' => 'شماره موبایل معتبر نیست.'])->withInput();
        }

        $user = User::query()->where('phone', $phone)->first();

        if (! $user) {
            return back()->withErrors(['phone' => 'این شماره برای هیچ کارمندی ثبت نشده است.'])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['phone' => 'حساب این کارمند غیرفعال است.'])->withInput();
        }

        if (! $user->can_login_otp) {
            return back()->withErrors(['phone' => 'ورود پیامکی برای این کارمند خاموش است.'])->withInput();
        }

        $code = (string) random_int(100000, 999999);

        LoginOtp::query()->where('phone', $phone)->delete();
        LoginOtp::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $sms->sendOtp($phone, $code);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['phone' => $result['message'] ?? 'ارسال کد ناموفق بود. تنظیمات پیامک را بررسی کنید.'])->withInput();
        }

        return redirect()
            ->route('login', ['otp' => 1])
            ->with('otp_sent', true)
            ->with('otp_phone', $phone)
            ->with('success', $result['message'] ?? 'کد تأیید ارسال شد.')
            ->with('debug_otp', $result['debug_code'] ?? null);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $phone = User::normalizePhone($data['phone']);
        $otp = LoginOtp::query()->where('phone', $phone)->latest('id')->first();

        if (! $otp || $otp->isExpired()) {
            throw ValidationException::withMessages(['code' => 'کد منقضی شده است. دوباره درخواست دهید.']);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'تعداد تلاش بیش از حد مجاز است.']);
        }

        if (! hash_equals($otp->code, $data['code'])) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'کد وارد شده نادرست است.']);
        }

        $user = User::query()->where('phone', $phone)->where('is_active', true)->first();
        if (! $user || ! $user->can_login_otp) {
            throw ValidationException::withMessages(['phone' => 'کاربر معتبر یافت نشد.']);
        }

        $otp->delete();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute()));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('gate');
    }
}
