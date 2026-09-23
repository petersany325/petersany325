<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Customer;

class PortalInviteTemplates
{
    public const KEY = 'sms_template_customer_portal_invite';

    public static function default(): string
    {
        return "سلام {name} عزیز\n"
            ."کارتابل مشتری {shop} آماده است.\n"
            ."برای ورود و پیگیری قبض‌ها روی لینک بزنید:\n"
            ."{login_url}\n"
            ."ورود با پیامک موبایل است.\n"
            .'تلفن دفتر: {office_phone}';
    }

    public static function template(): string
    {
        $raw = trim((string) AppSetting::getValue(self::KEY, ''));

        return $raw !== '' ? $raw : self::default();
    }

    public static function save(string $template): void
    {
        $text = trim($template);
        AppSetting::setValue(self::KEY, $text !== '' ? $text : self::default());
    }

    public static function loginUrl(): string
    {
        return url('/cartable');
    }

    public static function render(Customer $customer, ?string $template = null): string
    {
        $shop = shop_name();
        $office = function_exists('shop_office_phone') ? shop_office_phone() : '01144447220';

        return strtr($template ?: self::template(), [
            '{name}' => trim((string) $customer->name) ?: 'مشتری',
            '{shop}' => $shop,
            '{phone}' => (string) $customer->phone,
            '{login_url}' => self::loginUrl(),
            '{office_phone}' => $office,
        ]);
    }
}
