<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZarinPalService
{
    public function isConfigured(): bool
    {
        $merchant = $this->merchantId();

        return $merchant !== '' && strlen($merchant) >= 20;
    }

    public function isSandbox(): bool
    {
        return AppSetting::getValue('zarinpal_sandbox', '1') === '1'
            || (bool) env('ZARINPAL_SANDBOX', false);
    }

    public function merchantId(): string
    {
        return trim((string) (
            AppSetting::getValue('zarinpal_merchant_id')
            ?: env('ZARINPAL_MERCHANT_ID', '')
        ));
    }

    public function currency(): string
    {
        $c = strtoupper(trim((string) AppSetting::getValue('zarinpal_currency', 'IRT')));

        return in_array($c, ['IRT', 'IRR'], true) ? $c : 'IRT';
    }

    /**
     * @return array{ok:bool,authority?:string,fee?:int,message?:string,raw?:mixed}
     */
    public function request(int $amountToman, string $callbackUrl, string $description, ?string $mobile = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'مرچنت‌آیدی زرین‌پال تنظیم نشده است.'];
        }

        if ($amountToman < 1000) {
            return ['ok' => false, 'message' => 'حداقل مبلغ پرداخت ۱٬۰۰۰ تومان است.'];
        }

        $currency = $this->currency();
        $amount = $currency === 'IRR' ? $amountToman * 10 : $amountToman;

        $payload = [
            'merchant_id' => $this->merchantId(),
            'amount' => $amount,
            'currency' => $currency,
            'callback_url' => $callbackUrl,
            'description' => mb_substr($description, 0, 250),
        ];
        if ($mobile) {
            $payload['metadata'] = ['mobile' => $mobile];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 400)
                ->post($this->requestUrl(), $payload);

            $json = $response->json();
            $code = (int) data_get($json, 'data.code', data_get($json, 'errors.code', -1));
            $authority = (string) data_get($json, 'data.authority', '');

            if ($response->successful() && $code === 100 && $authority !== '') {
                return [
                    'ok' => true,
                    'authority' => $authority,
                    'fee' => (int) data_get($json, 'data.fee', 0),
                    'raw' => $json,
                ];
            }

            $message = (string) (
                data_get($json, 'errors.message')
                ?? data_get($json, 'data.message')
                ?? 'درخواست پرداخت زرین‌پال ناموفق بود.'
            );

            Log::warning('ZarinPal request failed', ['body' => $json, 'status' => $response->status()]);

            return ['ok' => false, 'message' => $message, 'raw' => $json];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'اتصال به زرین‌پال برقرار نشد: '.$e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,code?:int,ref_id?:string,card_pan?:string,message?:string,raw?:mixed}
     */
    public function verify(string $authority, int $amountToman): array
    {
        $currency = $this->currency();
        $amount = $currency === 'IRR' ? $amountToman * 10 : $amountToman;

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 400)
                ->post($this->verifyUrl(), [
                    'merchant_id' => $this->merchantId(),
                    'amount' => $amount,
                    'authority' => $authority,
                ]);

            $json = $response->json();
            $code = (int) data_get($json, 'data.code', -1);

            if (in_array($code, [100, 101], true)) {
                return [
                    'ok' => true,
                    'code' => $code,
                    'ref_id' => (string) data_get($json, 'data.ref_id', ''),
                    'card_pan' => (string) data_get($json, 'data.card_pan', ''),
                    'message' => (string) data_get($json, 'data.message', 'Verified'),
                    'raw' => $json,
                ];
            }

            $message = (string) (
                data_get($json, 'errors.message')
                ?? data_get($json, 'data.message')
                ?? 'تأیید پرداخت ناموفق بود.'
            );

            return ['ok' => false, 'code' => $code, 'message' => $message, 'raw' => $json];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'خطا در تأیید زرین‌پال: '.$e->getMessage()];
        }
    }

    public function startPayUrl(string $authority): string
    {
        return $this->basePgUrl().'/StartPay/'.urlencode($authority);
    }

    private function requestUrl(): string
    {
        return $this->baseApiUrl().'/pg/v4/payment/request.json';
    }

    private function verifyUrl(): string
    {
        return $this->baseApiUrl().'/pg/v4/payment/verify.json';
    }

    private function baseApiUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.zarinpal.com'
            : 'https://payment.zarinpal.com';
    }

    private function basePgUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.zarinpal.com/pg'
            : 'https://payment.zarinpal.com/pg';
    }
}
