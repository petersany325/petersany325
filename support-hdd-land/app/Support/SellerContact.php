<?php

namespace App\Support;

/**
 * Public seller support contact shown on customer installs («تماس با ما»).
 */
class SellerContact
{
    public static function title(): string
    {
        return 'سایت مدیریت تعمیرکاران سرزمین هارد';
    }

    public static function phone(): string
    {
        return '01144447220';
    }

    public static function whatsapp(): string
    {
        return '09123486391';
    }

    public static function website(): string
    {
        return 'www.hdd-land.ir';
    }

    public static function websiteUrl(): string
    {
        return 'https://www.hdd-land.ir';
    }

    public static function whatsappUrl(): string
    {
        $digits = preg_replace('/\D+/', '', self::whatsapp()) ?: '';
        if (str_starts_with($digits, '0')) {
            $digits = '98'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }

    /** @return array{title:string,phone:string,whatsapp:string,website:string,website_url:string,whatsapp_url:string} */
    public static function all(): array
    {
        return [
            'title' => self::title(),
            'phone' => self::phone(),
            'whatsapp' => self::whatsapp(),
            'website' => self::website(),
            'website_url' => self::websiteUrl(),
            'whatsapp_url' => self::whatsappUrl(),
        ];
    }
}
