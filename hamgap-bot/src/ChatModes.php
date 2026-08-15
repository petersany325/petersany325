<?php
declare(strict_types=1);

/** Anonymous chat modes: normal / hot / vipclub */
final class ChatModes
{
    public const NORMAL = 'normal';
    public const HOT = 'hot';
    public const VIP = 'vipclub';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::NORMAL, self::HOT, self::VIP];
    }

    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::all(), true);
    }

    public static function label(string $mode): string
    {
        return match ($mode) {
            self::HOT => 'چت هات',
            self::VIP => 'کلاب VIP',
            default => 'چت معمولی',
        };
    }

    public static function rules(string $mode, Settings $settings): string
    {
        $custom = trim($settings->get('rules_' . $mode, ''));
        if ($custom !== '') {
            return $custom;
        }
        return match ($mode) {
            self::HOT =>
                "🔥 <b>قوانین چت هات</b>\n\n" .
                "این روم برای کسانی است که دنبال آشنایی و رابطه هستند و فضای گفتگو آزادتر است.\n\n" .
                "✅ احترام متقابل الزامی است.\n" .
                "✅ اجبار، تهدید، اخاذی و آزار ممنوع است.\n" .
                "✅ انتشار عکس/ویدیوی دیگران بدون رضایت ممنوع است.\n" .
                "✅ کلاهبرداری و درخواست پول ممنوع است.\n\n" .
                "با ورود، این قوانین را می‌پذیری.",
            self::VIP =>
                "⭐ <b>قوانین کلاب VIP</b>\n\n" .
                "این چت اختصاصی برای آشنایی جدی، پارتنر و رابطه سالم است.\n\n" .
                "✅ لحن مودب و سطح‌بالا — توهین و بی‌احترامی ممنوع.\n" .
                "✅ مشخصات و شهر باید واقعی باشد (موقعیت مکانی برای تأیید استفاده می‌شود).\n" .
                "✅ کلمات رکیک طبق لیست مدیریت ممنوع است و منجر به اخراج می‌شود.\n" .
                "✅ گزارش تخلف را جدی بگیر؛ نقض مقررات = خروج از کلاب.\n\n" .
                "با ورود، این قوانین را می‌پذیری.",
            default =>
                "💬 <b>قوانین چت معمولی</b>\n\n" .
                "این چت برای پیدا کردن دوست و گفتگوی محترمانه است.\n\n" .
                "✅ بدون توهین، تمسخر و آزار.\n" .
                "✅ بدون اسپم و تبلیغات.\n" .
                "✅ احترام به حریم خصوصی طرف مقابل.\n" .
                "✅ درخواست اطلاعات حساس یا پول ممنوع است.\n\n" .
                "با ورود، این قوانین را می‌پذیری.",
        };
    }
}
