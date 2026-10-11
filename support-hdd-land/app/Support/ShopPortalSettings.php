<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Editable web/mobile portal contact shown to customers (cartable).
 * Never defaults to seller HDD Land phone — shops set their own numbers.
 */
class ShopPortalSettings
{
    /** Legacy seller default that must not leak into customer installs. */
    public const SELLER_DEFAULT_PHONE = '01144447220';

    public static function all(): array
    {
        return [
            'office_phone' => self::officePhone(),
            'mobile_phone' => self::mobilePhone(),
            'whatsapp' => self::whatsapp(),
            'address' => self::address(),
            'welcome_text' => self::welcomeText(),
            'show_contact' => self::showContact(),
            'stored_office_phone' => self::storedOfficePhone(),
            'stored_mobile_phone' => self::raw('portal_mobile_phone'),
            'stored_whatsapp' => self::raw('portal_whatsapp'),
            'stored_address' => self::raw('portal_address'),
            'stored_welcome_text' => self::raw('portal_welcome_text'),
        ];
    }

    public static function showContact(): bool
    {
        return self::raw('portal_show_contact', '1') !== '0';
    }

    public static function officePhone(): string
    {
        if (! self::showContact()) {
            return '';
        }

        $phone = self::normalizePhone(self::storedOfficePhone());
        if ($phone !== '') {
            return $phone;
        }

        $fromInvoice = self::firstInvoicePhone();
        if ($fromInvoice !== '') {
            return $fromInvoice;
        }

        $legacy = self::normalizePhone(self::raw('remote_part_preorder_office_phone'));
        if ($legacy !== '' && ! self::isSellerDefault($legacy)) {
            return $legacy;
        }

        return '';
    }

    public static function mobilePhone(): string
    {
        if (! self::showContact()) {
            return '';
        }

        return self::normalizePhone(self::raw('portal_mobile_phone'));
    }

    public static function whatsapp(): string
    {
        if (! self::showContact()) {
            return '';
        }

        return self::normalizePhone(self::raw('portal_whatsapp'));
    }

    public static function address(): string
    {
        $addr = trim(self::raw('portal_address'));
        if ($addr !== '') {
            return $addr;
        }

        try {
            return trim((string) AppSetting::getValue('invoice_address', ''));
        } catch (\Throwable) {
            return '';
        }
    }

    public static function welcomeText(): string
    {
        return trim(self::raw('portal_welcome_text'));
    }

    public static function whatsappUrl(): string
    {
        $digits = preg_replace('/\D+/', '', self::whatsapp()) ?: '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '98'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }

    public static function save(array $data): void
    {
        $office = self::normalizePhone((string) ($data['office_phone'] ?? ''));
        $mobile = self::normalizePhone((string) ($data['mobile_phone'] ?? ''));
        $whatsapp = self::normalizePhone((string) ($data['whatsapp'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));
        $welcome = trim((string) ($data['welcome_text'] ?? ''));
        $show = ! empty($data['show_contact']);

        AppSetting::setValue('portal_office_phone', $office);
        AppSetting::setValue('portal_mobile_phone', $mobile);
        AppSetting::setValue('portal_whatsapp', $whatsapp);
        AppSetting::setValue('portal_address', $address);
        AppSetting::setValue('portal_welcome_text', $welcome);
        AppSetting::setValue('portal_show_contact', $show ? '1' : '0');

        // Keep remote-preorder SMS phone in sync when shop sets portal contact.
        if ($office !== '') {
            AppSetting::setValue('remote_part_preorder_office_phone', $office);
        } elseif (self::isSellerDefault(self::raw('remote_part_preorder_office_phone'))) {
            AppSetting::setValue('remote_part_preorder_office_phone', '');
        }
    }

    public static function storedOfficePhone(): string
    {
        return self::raw('portal_office_phone');
    }

    public static function isSellerDefault(string $phone): bool
    {
        $n = self::normalizePhone($phone);

        return $n !== '' && $n === self::SELLER_DEFAULT_PHONE;
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', ascii_digits(trim($phone))) ?? '';

        return $phone;
    }

    private static function firstInvoicePhone(): string
    {
        try {
            $raw = trim((string) AppSetting::getValue('invoice_phones', ''));
        } catch (\Throwable) {
            return '';
        }
        if ($raw === '') {
            return '';
        }
        foreach (preg_split('/[|،,;\/]+/u', $raw) ?: [] as $part) {
            $n = self::normalizePhone((string) $part);
            if ($n !== '' && ! self::isSellerDefault($n)) {
                return $n;
            }
        }

        return '';
    }

    private static function raw(string $key, string $default = ''): string
    {
        try {
            return trim((string) (AppSetting::getValue($key, $default) ?? $default));
        } catch (\Throwable) {
            return $default;
        }
    }
}
