<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NiazpardazSmsService
{
    public function sendOtp(string $phone, string $code): array
    {
        $shop = AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));
        $message = "کد ورود {$shop}: {$code}";

        return $this->send($phone, $message, $code);
    }

    public function sendTest(string $phone): array
    {
        $shop = AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));
        $message = "تست پنل پیامک {$shop} — ارسال موفق بود.";

        return $this->send($phone, $message);
    }

    /**
     * Welcome SMS for newly registered staff with login link.
     *
     * @return array{ok:bool,message:string,text?:string}
     */
    public function sendEmployeeWelcome(User $user): array
    {
        $phone = User::normalizePhone($user->phone);
        if (! $phone) {
            return ['ok' => false, 'message' => 'شماره موبایل کارمند معتبر نیست.'];
        }

        $message = \App\Support\StaffSmsTemplates::renderEmployee($user);
        $result = $this->send($phone, $message);
        $result['text'] = $message;

        return $result;
    }

    public function send(string $phone, string $message, ?string $debugCode = null): array
    {
        $username = AppSetting::getValue('niazpardaz_username', env('NIAZPARDAZ_USERNAME'));
        $password = AppSetting::getValue('niazpardaz_password', env('NIAZPARDAZ_PASSWORD'));
        $apiKey = AppSetting::getValue('niazpardaz_api_key', env('NIAZPARDAZ_API_KEY'));
        $from = AppSetting::getValue('niazpardaz_from', env('NIAZPARDAZ_FROM_NUMBER'));

        if (! $from) {
            return ['ok' => false, 'message' => 'شماره فرستنده نیازپرداز تنظیم نشده است.'];
        }

        if ($username && $password) {
            $panel = $this->sendViaPanel($username, $password, $from, $phone, $message);
            if (($panel['ok'] ?? false) === true) {
                return $panel;
            }

            $rest = $this->sendViaRestApi($username, $password, $from, $phone, $message, $apiKey);
            if ($rest['ok']) {
                return $rest;
            }

            return $panel ?: $rest;
        }

        if ($apiKey) {
            return $this->sendViaRestApi(null, null, $from, $phone, $message, $apiKey);
        }

        if (app()->environment('local')) {
            Log::info('SMS fallback', ['phone' => $phone, 'message' => $message]);

            $result = ['ok' => true, 'message' => 'پیامک در حالت توسعه ثبت شد (ارسال واقعی انجام نشد).'];
            if ($debugCode) {
                $result['debug_code'] = $debugCode;
            }

            return $result;
        }

        return ['ok' => false, 'message' => 'اطلاعات پنل نیازپرداز در تنظیمات وارد نشده است.'];
    }

    private function sendViaPanel(string $username, string $password, string $from, string $to, string $message): array
    {
        try {
            $response = Http::asForm()->timeout(8)->connectTimeout(4)->post('https://panel.niazpardaz-sms.com/SMSInOutBox/Send', [
                'UserName' => $username,
                'Password' => $password,
                'From' => $from,
                'To' => $to,
                'Message' => $message,
            ]);

            if ($response->successful()) {
                $body = trim((string) $response->body());
                // Some panel endpoints return HTTP 200 with numeric error codes
                if ($body !== '' && preg_match('/^-\d+$/', $body)) {
                    Log::warning('Niazpardaz panel post error code', ['body' => $body]);
                } else {
                    return ['ok' => true, 'message' => 'پیامک با موفقیت ارسال شد.', 'provider' => 'panel-post', 'raw' => $body];
                }
            }

            $get = Http::timeout(20)->get('https://panel.niazpardaz-sms.com/SMSInOutBox/SendSms', [
                'username' => $username,
                'password' => $password,
                'from' => $from,
                'to' => $to,
                'text' => $message,
            ]);

            if ($get->successful()) {
                $body = trim((string) $get->body());
                if ($body !== '' && preg_match('/^-\d+$/', $body)) {
                    Log::warning('Niazpardaz panel get error code', ['body' => $body]);
                } else {
                    return ['ok' => true, 'message' => 'پیامک با موفقیت ارسال شد.', 'provider' => 'panel-get', 'raw' => $body];
                }
            }

            Log::warning('Niazpardaz panel send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'get_status' => $get->status(),
                'get_body' => $get->body(),
            ]);

            return [
                'ok' => false,
                'message' => 'ارسال از پنل ناموفق بود: HTTP '.$response->status(),
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Niazpardaz panel exception', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'خطا در ارتباط با پنل: '.$e->getMessage()];
        }
    }

    private function sendViaRestApi(?string $username, ?string $password, string $from, string $to, string $message, ?string $apiKey = null): array
    {
        try {
            $payload = [
                'fromNumber' => $from,
                'toNumbers' => $to,
                'messageContent' => $message,
                'isFlash' => false,
                'sendDelay' => 0,
            ];

            if ($apiKey) {
                $payload['apiKey'] = $apiKey;
            }
            if ($username) {
                $payload['userName'] = $username;
            }
            if ($password) {
                $payload['password'] = $password;
            }

            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->post('http://in.payamak-service.ir/api/v2/RestWebApi/SendBatchSms', $payload);

            $data = $response->json();
            $code = (int) ($data['ResultCode'] ?? $data['resultCode'] ?? -1);

            if ($response->successful() && $code === 0) {
                return ['ok' => true, 'message' => 'پیامک با موفقیت ارسال شد.', 'provider' => 'rest'];
            }

            Log::warning('Niazpardaz REST send failed', ['body' => $data ?: $response->body()]);

            return [
                'ok' => false,
                'message' => 'ارسال پیامک ناموفق بود. کد: '.($code !== -1 ? $code : $response->status()),
                'raw' => $data ?: $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Niazpardaz REST exception', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'خطا در ارتباط با پنل نیازپرداز.'];
        }
    }
}
