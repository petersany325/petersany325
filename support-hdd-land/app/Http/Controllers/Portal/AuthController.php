<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoginOtp;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('portal_customer_id')) {
            return redirect()->route('portal.home');
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
            return back()->withErrors(['phone' => 'شماره موبایل معتبر نیست.'])->withInput();
        }

        $customer = Customer::query()->where('phone', $phone)->first();
        if (! $customer) {
            // also try without leading zero variants already normalized
            return back()->withErrors(['phone' => 'این شماره به‌عنوان مشتری ثبت نشده است. با پذیرش تماس بگیرید.'])->withInput();
        }

        $code = (string) random_int(100000, 999999);
        $message = "کد ورود کارتابل مشتری سرزمین هارد: {$code}";

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
        $otp = LoginOtp::query()->where('phone', $phone)->latest('id')->first();

        if (! $otp || $otp->isExpired()) {
            throw ValidationException::withMessages(['code' => 'کد منقضی شده است. دوباره درخواست دهید.']);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'تعداد تلاش بیش از حد مجاز است.']);
        }

        if (! hash_equals($otp->code, trim($data['code']))) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'کد وارد شده نادرست است.']);
        }

        $customer = Customer::query()->where('phone', $phone)->first();
        if (! $customer) {
            throw ValidationException::withMessages(['phone' => 'مشتری یافت نشد.']);
        }

        $otp->delete();
        $request->session()->regenerate();
        $request->session()->put('portal_customer_id', $customer->id);

        return redirect()->route('portal.home');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('portal_customer_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('gate');
    }
}
