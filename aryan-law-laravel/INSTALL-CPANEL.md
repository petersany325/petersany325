# aryan-law-laravel — نصب روی هاست cPanel (لاراول + Filament)

> سایت مؤسسه حقوقی آریان — نه Windex، نه WinFOF.

پکیج ZIP شامل `vendor` است. پنل ادمین حرفه‌ای با **Filament** روی مسیر `/admin` فعال است.

## پیش‌نیاز
- PHP **8.3** ترجیحاً `ea-php83` (mbstring/fileinfo/pdo_mysql/intl/gd)
- MySQL خالی + یوزر
- نوشتن روی `storage/` و `bootstrap/cache/`

## نصب
1. Extract ZIP
2. Document Root روی `public` (یا اتصال `public_html` به پروژه طبق راهنمای قبلی)
3. باز کردن `/install` و وارد کردن فقط مشخصات دیتابیس
4. دریافت ایمیل/رمز ادمین → ورود به `/admin`

## امکانات پنل
- درخواست‌های مشاوره و نوبت‌ها
- خدمات، مقالات، صفحات، تیم، FAQ، نظرات
- تنظیمات برند و تماس
- داشبورد آماری

## صفحات سایت
`/`, `/blog`, `/team`, `/faq`, `/p/{slug}`
