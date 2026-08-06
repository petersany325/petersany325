# نصب روی هاست cPanel — لاراول ۱۳.۸

فایل ZIP نصبی (`law-laravel-cpanel-install.zip`) شامل `vendor` است و روی هاست به Composer نیاز ندارد.

## پیش‌نیاز هاست
- **PHP 8.3** (در MultiPHP نسخهٔ `ea-php83` را انتخاب کنید؛ بعضی `alt-php83`ها mbstring/fileinfo ندارند)
- افزونه‌ها: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`
- یک دیتابیس MySQL خالی + یوزر با دسترسی کامل
- دسترسی نوشتن به `storage/` و `bootstrap/cache/`

## نصب سریع
1. ZIP را آپلود و Extract کنید.
2. محتویات `public/` را به `public_html` وصل کنید و در `index.php` مسیر را به پوشهٔ اصلی پروژه تنظیم کنید  
   **یا** Document Root دامنه را روی `.../law-laravel-host/public` بگذارید.
3. دامنه را باز کنید → `/install`
4. فقط مشخصات دیتابیس را وارد کنید.
5. ایمیل و رمز ادمین نمایش داده می‌شود → وارد `/admin` شوید.

## نکات
- نصب فقط یک‌بار انجام می‌شود (`storage/app/installed`).
- برای نصب مجدد همان فایل را حذف کنید.
- بعد از نصب، رمز ادمین را از پنل عوض کنید.
