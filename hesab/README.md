# سامانه حسابداری تحت وب — hesab.hdd-land.ir

وب‌اپ حسابداری به سبک نرم‌افزار (RTL) با هسته سند دوطرفه.

## ماژول‌های MVP
- کدینگ حساب (seed از فایل اکسل پیشنهادی)
- اسناد حسابداری (پیش‌نویس / قطعی)
- دفتر حساب و تراز آزمایشی
- طرف‌حساب و فاکتور فروش (+ سند خودکار)

## استقرار روی cPanel
1. ساب‌دامین `hesab.hdd-land.ir` با Document Root: `/home/USER/hesab`
2. دیتابیس MySQL بسازید و کاربر را با ALL PRIVILEGES وصل کنید
3. محتوای این پوشه را داخل Document Root آپلود کنید
4. باز کنید: `https://hdd-land.ir/hesab/install`

## ورود پیش‌فرض بعد از نصب
- ایمیل: مقدار واردشده در نصب (پیشنهادی: `admin@hesab.local`)
- رمز: مقدار واردشده در نصب

فایل `config.sample.php` نمونه تنظیمات است؛ بعد از نصب `config.php` ساخته می‌شود.

## نرم‌افزار نصبی ویندوز (مجزا)

- پروژه و نصب‌کننده: `winclient/` — راهنمای دانلود Setup.exe در `winclient/README.md`
- ساخت خودکار: GitHub Actions → workflow `Hesab Windows Installer`
- فایل نصب: `Hesab-HDD-Setup-1.0.0.exe` (بدون نیاز به نصب .NET)
- اسکریپت دیتابیس (داخل نصب‌کننده هم هست): `sql/sqlserver_hesab.sql`
- API همگام‌سازی وب: `GET/POST /api/sync`
