<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Services\ZarinPalService;

class PaymentGateways
{
    /**
     * Bank homepage links (until bank IPG credentials arrive).
     *
     * @return list<array{key:string,label:string,hint:string,tone:string,default:string}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => 'melli',
                'label' => 'بانک ملی',
                'hint' => 'لینک موقت تا اتصال IPG سداد',
                'tone' => 'blue',
                'default' => 'https://bmi.ir/',
            ],
            [
                'key' => 'tejarat',
                'label' => 'بانک تجارت',
                'hint' => 'لینک موقت تا اتصال IPG',
                'tone' => 'rose',
                'default' => 'https://www.tejaratbank.ir/',
            ],
            [
                'key' => 'pasargad',
                'label' => 'بانک پاسارگاد',
                'hint' => 'لینک موقت تا اتصال IPG',
                'tone' => 'yellow',
                'default' => 'https://www.bpi.ir/',
            ],
            [
                'key' => 'saman',
                'label' => 'بانک سامان',
                'hint' => 'لینک موقت تا اتصال IPG',
                'tone' => 'teal',
                'default' => 'https://www.sb24.ir/',
            ],
        ];
    }

    public static function settingKey(string $gatewayKey): string
    {
        return 'pay_link_'.$gatewayKey;
    }

    /**
     * @return list<array{key:string,label:string,hint:string,tone:string,url:string}>
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::definitions() as $def) {
            $url = trim((string) AppSetting::getValue(self::settingKey($def['key']), $def['default']));
            $out[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'hint' => $def['hint'],
                'tone' => $def['tone'],
                'url' => $url,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{key:string,label:string,hint:string,tone:string,url:string}>
     */
    public static function active(): array
    {
        return array_values(array_filter(self::all(), function (array $g) {
            return $g['url'] !== '' && preg_match('#^https?://#i', $g['url']);
        }));
    }

    public static function showOnInvoice(): bool
    {
        return AppSetting::getValue('pay_links_show_invoice', '1') !== '0';
    }

    public static function showOnReception(): bool
    {
        return AppSetting::getValue('pay_links_show_reception', '1') !== '0';
    }

    public static function zarinpal(): array
    {
        $svc = app(ZarinPalService::class);

        return [
            'configured' => $svc->isConfigured(),
            'sandbox' => $svc->isSandbox(),
            'merchant_id' => $svc->merchantId(),
            'currency' => $svc->currency(),
        ];
    }
}
