<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePortalCustomer;
use App\Models\Customer;
use App\Models\LoginOtp;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('portal_customer_id')) {
            return redirect()->route('portal.home');
        }

        // If session was dropped (refresh / CSRF / PWA) but remember cookie is valid, restore quietly
        $restored = app(EnsurePortalCustomer::class)->restoreCustomerId($request);
        if ($restored) {
            $request->session()->put('portal_customer_id', $restored);
            $request->session()->put('portal_last_seen', now()->timestamp);

            return redirect()->intended(route('portal.home'));
        }

        return response()
            ->view('portal.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function sendOtp(Request $request, NiazpardazSmsService $sms)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = User::normalizePhone($data['phone']);
        if (! $phone || strlen($phone) < 10) {
            return back()->withErrors(['phone' => 'شماره موبایل معتبر نیست. ارقام را انگلیسی وارد کنید یا دوباره تلاش کنید.'])->withInput();
        }

        $customer = Customer::findByPhone($phone);
        if (! $customer) {
            return back()->withErrors(['phone' => 'این شماره به‌عنوان مشتری ثبت نشده است. با پذیرش تماس بگیرید.'])->withInput();
        }

        // Always store canonical 09xxxxxxxxx on the OTP + prefer fixing customer phone
        $phone = User::normalizePhone($customer->phone) ?: $phone;
        if ($customer->phone !== $phone) {
            $customer->forceFill(['phone' => $phone])->save();
        }

        $code = (string) random_int(100000, 999999);
        $shop = \App\Models\AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));
        $message = "کد ورود کارتابل مشتری {$shop}: {$code}";

        LoginOtp::query()->where('phone', $phone)->delete();
        LoginOtp::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $sms->send($phone, $message, $code);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['phone' => $result['message'] ?? 'ارسال کد ناموفق بود.'])->withInput();
        }

        $debug = $result['debug_code'] ?? null;
        if (! $debug && \App\Models\AppSetting::getValue('portal_otp_debug', '0') === '1') {
            $debug = $code;
        }

        return redirect()
            ->route('portal.login')
            ->with('otp_sent', true)
            ->with('otp_phone', $phone)
            ->with('success', 'کد تأیید به موبایل شما ارسال شد.')
            ->with('debug_otp', $debug);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $phone = User::normalizePhone($data['phone']);
        $digitMap = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $code = preg_replace('/\D+/', '', strtr((string) $data['code'], $digitMap)) ?? '';

        $otp = LoginOtp::query()->where('phone', $phone)->latest('id')->first();

        if (! $otp || $otp->isExpired()) {
            throw ValidationException::withMessages(['code' => 'کد منقضی شده است. دوباره درخواست دهید.']);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'تعداد تلاش بیش از حد مجاز است.']);
        }

        if ($code === '' || ! hash_equals($otp->code, $code)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'کد وارد شده نادرست است.']);
        }

        $customer = Customer::findByPhone($phone);
        if (! $customer) {
            throw ValidationException::withMessages(['phone' => 'مشتری یافت نشد.']);
        }

        $otp->delete();
        $request->session()->regenerate();
        $request->session()->put('portal_customer_id', $customer->id);
        $request->session()->put('portal_last_seen', now()->timestamp);

        // Persistent portal login (30 days) so refresh / PWA reopen does not force SMS again
        $minutes = 60 * 24 * 30;
        Cookie::queue(cookie(
            EnsurePortalCustomer::REMEMBER_COOKIE,
            encrypt([
                'cid' => $customer->id,
                'exp' => now()->addMinutes($minutes)->timestamp,
            ]),
            $minutes,
            '/',
            null,
            true,
            true,
            false,
            'Lax'
        ));

        return redirect()->route('portal.home');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('portal_customer_id');
        $request->session()->forget('portal_last_seen');
        Cookie::queue(Cookie::forget(EnsurePortalCustomer::REMEMBER_COOKIE));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('gate');
    }
}
